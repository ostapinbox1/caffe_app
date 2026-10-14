<?php

use App\Actions\Shifts\EndShift;
use App\Actions\Shifts\StartShift;
use App\Enums\ChecklistDeadline;
use App\Enums\ChecklistRunStatus;
use App\Models\ChecklistRun;
use App\Models\ChecklistTemplate;
use App\Models\Position;
use App\Models\User;
use App\Notifications\ChecklistOverdue;
use App\Notifications\ChecklistSubmittedWithGaps;
use App\Notifications\ShiftEndedWithOpenChecklists;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Кофейня с бариста и двумя чек-листами: утренний к 10:00 и закрытие до конца смены.
 */
function baristaShift(): object
{
    $k = kitchen();
    $barista = Position::factory()->create(['cafe_id' => $k->cafe->id, 'name' => 'Бариста']);
    $k->staff->update(['position_id' => $barista->id]);

    $make = function (string $name, ChecklistDeadline $deadline, ?string $due, array $sections) use ($k, $barista): ChecklistTemplate {
        $template = ChecklistTemplate::withoutGlobalScopes()->create([
            'cafe_id' => $k->cafe->id, 'name' => $name, 'deadline_type' => $deadline, 'due_time' => $due,
        ]);
        foreach ($sections as $sectionTitle => $items) {
            $section = $template->sections()->create(['title' => $sectionTitle]);
            foreach ($items as $sort => $text) {
                $section->items()->create(['text' => $text, 'sort' => $sort]);
            }
        }
        $template->positions()->attach($barista);

        return $template;
    };

    $k->position = $barista;
    $k->morning = $make('Открытие смены', ChecklistDeadline::Time, '10:00', ['Бар' => ['Включить кофемашину', 'Проверить молоко']]);
    $k->closing = $make('Закрытие смены', ChecklistDeadline::ShiftEnd, null, ['Оборудование' => ['Кофемашина выключена'], 'Финал' => ['Двери закрыты']]);

    return $k;
}

test('starting a shift hands out checklists of the position', function () {
    $this->travelTo(today()->setTime(8, 0));
    $k = baristaShift();
    $this->actingAs($k->staff);

    Livewire::test('pages::dashboard')
        ->assertSee('Смена не начата')
        ->assertSee('Открытие смены, Закрытие смены')
        ->call('startShift')
        ->assertSee('Смена идёт');

    $runs = ChecklistRun::with('items')->orderBy('id')->get();

    expect($runs)->toHaveCount(2)
        ->and($runs[0]->title)->toBe('Открытие смены')
        ->and($runs[0]->due_at->format('H:i'))->toBe('10:00')
        ->and($runs[0]->items)->toHaveCount(2)
        ->and($runs[1]->due_at)->toBeNull()
        ->and($runs[1]->items->pluck('section')->all())->toBe(['Оборудование', 'Финал'])
        ->and($runs[0]->items->pluck('sort')->all())->toBe([1, 2]);
});

test('late shift start gives an hour for a timed checklist', function () {
    $this->travelTo(today()->setTime(11, 30));
    $k = baristaShift();

    app(StartShift::class)->handle($k->staff);

    expect(ChecklistRun::where('title', 'Открытие смены')->first()->due_at->format('H:i'))->toBe('12:30');
});

test('personal assignment, inactive templates and other positions', function () {
    $k = baristaShift();
    $cook = User::factory()->create(['cafe_id' => $k->cafe->id]);
    $k->closing->users()->attach($cook);
    $k->morning->update(['is_active' => false]);

    app(StartShift::class)->handle($cook);
    app(StartShift::class)->handle($k->staff);

    expect(ChecklistRun::where('user_id', $cook->id)->pluck('title')->all())->toBe(['Закрытие смены'])
        ->and(ChecklistRun::where('user_id', $k->staff->id)->pluck('title')->all())->toBe(['Закрытие смены']);
});

test('shift cannot be opened twice', function () {
    $k = baristaShift();
    app(StartShift::class)->handle($k->staff);

    expect(fn () => app(StartShift::class)->handle($k->staff))->toThrow(ValidationException::class);
});

test('employee ticks items and submits the checklist', function () {
    $k = baristaShift();
    app(StartShift::class)->handle($k->staff);
    $run = ChecklistRun::where('title', 'Закрытие смены')->with('items')->first();
    $this->actingAs($k->staff);

    $page = Livewire::test('pages::checklists.show', ['run' => $run])
        ->assertSee('Отмечено 0 из 2');

    foreach ($run->items as $item) {
        $page->call('toggle', $item->id);
    }

    $page->assertSee('Отмечено 2 из 2')->call('complete')->assertHasNoErrors();

    expect($run->fresh(['items', 'shift'])->status())->toBe(ChecklistRunStatus::Done);
});

test('submitting with gaps needs a comment and notifies the manager', function () {
    Notification::fake();
    $k = baristaShift();
    app(StartShift::class)->handle($k->staff);
    $run = ChecklistRun::where('title', 'Закрытие смены')->first();
    $this->actingAs($k->staff);

    Livewire::test('pages::checklists.show', ['run' => $run])
        ->call('complete')
        ->assertHasErrors('comment')
        ->set('comment', 'Сломался выключатель на летнике')
        ->call('complete')
        ->assertHasNoErrors();

    expect($run->fresh(['items', 'shift'])->status())->toBe(ChecklistRunStatus::DoneWithGaps);
    Notification::assertSentTo($k->manager, ChecklistSubmittedWithGaps::class);
});

test('colleague cannot open or tick someone else\'s checklist, manager can only view', function () {
    $k = baristaShift();
    app(StartShift::class)->handle($k->staff);
    $run = ChecklistRun::with('items')->first();
    $colleague = User::factory()->create(['cafe_id' => $k->cafe->id]);

    $this->actingAs($colleague)->get(route('checklists.show', $run))->assertNotFound();

    $this->actingAs($k->manager)->get(route('checklists.show', $run))->assertOk()->assertSee('Анна Бариста');
    expect(fn () => $run->toggle($run->items->first()->id, $k->manager))
        ->toThrow(HttpException::class);
});

test('ending a shift with open checklists notifies managers', function () {
    Notification::fake();
    $k = baristaShift();
    app(StartShift::class)->handle($k->staff);

    app(EndShift::class)->handle($k->staff);

    $runs = ChecklistRun::with(['items', 'shift'])->get();
    expect($runs->every(fn (ChecklistRun $run) => $run->status() === ChecklistRunStatus::NotDone))->toBeTrue();
    Notification::assertSentTo($k->manager, ShiftEndedWithOpenChecklists::class);
});

test('overdue checklists are reported once', function () {
    Notification::fake();
    $this->travelTo(today()->setTime(8, 0));
    $k = baristaShift();
    app(StartShift::class)->handle($k->staff);

    $this->travelTo(today()->setTime(10, 5));
    $this->artisan('checklists:notify-overdue')->assertSuccessful();
    $this->artisan('checklists:notify-overdue')->assertSuccessful();

    Notification::assertSentToTimes($k->manager, ChecklistOverdue::class, 1);
    Notification::assertSentToTimes($k->staff, ChecklistOverdue::class, 1);
});

test('team board is for managers and shows progress', function () {
    $k = baristaShift();
    app(StartShift::class)->handle($k->staff);

    $this->actingAs($k->staff)->get(route('team'))->assertForbidden();

    $this->actingAs($k->manager)->get(route('team'))
        ->assertOk()
        ->assertSee('Анна Бариста')
        ->assertSee('в смене')
        ->assertSee('Закрытие смены');
});

test('checklist pages render for the employee', function () {
    $k = baristaShift();
    $this->actingAs($k->staff);

    $this->get(route('checklists.index'))->assertOk()->assertSee('Смена не начата');

    app(StartShift::class)->handle($k->staff);
    $this->get(route('checklists.index'))->assertOk()->assertSee('Открытие смены');
    $this->get(route('dashboard'))->assertOk()->assertSee('Чек-листы на смену');
});
