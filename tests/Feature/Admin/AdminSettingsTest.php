<?php

use App\Enums\ChecklistDeadline;
use App\Filament\Resources\ChecklistTemplates\Pages\CreateChecklistTemplate;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\ChecklistTemplate;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('settings are for managers; guests go to the cabinet login', function () {
    $k = kitchen();

    $this->get('/admin')->assertRedirect(route('login'));
    $this->actingAs($k->staff)->get('/admin/users')->assertForbidden();

    $this->actingAs($k->manager);
    foreach (['/admin/users', '/admin/positions', '/admin/checklist-templates', '/admin/checklist-templates/create',
        '/admin/catalog-items', '/admin/catalog-categories', '/admin/request-sections', '/admin/cafes'] as $url) {
        $this->get($url)->assertOk();
    }
    $this->get('/admin/users')->assertSee('Бариста Анна')->assertSee('Вернуться в кабинет');
    $this->get('/admin/purchase-requests')->assertNotFound();
});

test('manager builds a checklist in the constructor', function () {
    $k = kitchen();
    $barista = Position::factory()->create(['cafe_id' => $k->cafe->id, 'name' => 'Бариста']);
    $this->actingAs($k->manager);

    Livewire::test(CreateChecklistTemplate::class)
        ->fillForm([
            'name' => 'Открытие смены бариста',
            'deadline_type' => ChecklistDeadline::Time->value,
            'due_time' => '10:00',
            'positions' => [$barista->id],
            'sections' => [
                ['title' => 'Бар', 'items' => [['text' => 'Включить кофемашину', 'hint' => null], ['text' => 'Промыть холдеры', 'hint' => 'горячей водой']]],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $template = ChecklistTemplate::with(['sections.items', 'positions'])->firstOrFail();
    expect($template->cafe_id)->toBe($k->cafe->id)
        ->and($template->deadlineLabel())->toBe('до 10:00')
        ->and($template->positions->pluck('name')->all())->toBe(['Бариста'])
        ->and($template->sections->first()->items->pluck('text')->all())->toBe(['Включить кофемашину', 'Промыть холдеры']);
});

test('changing a name in settings does not touch the password', function () {
    $k = kitchen();
    $this->actingAs($k->manager);
    $hash = $k->staff->password;

    Livewire::test(EditUser::class, ['record' => $k->staff->getRouteKey()])
        ->fillForm(['first_name' => 'Аня'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($k->staff->fresh()->name)->toBe('Аня Бариста')
        ->and($k->staff->fresh()->password)->toBe($hash);
});

test('password is changed with a separate button', function () {
    $k = kitchen();
    $this->actingAs($k->manager);

    Livewire::test(EditUser::class, ['record' => $k->staff->getRouteKey()])
        ->callAction('changePassword', data: ['password' => 'новый-пароль-123'])
        ->assertHasNoActionErrors();

    expect(Hash::check('новый-пароль-123', $k->staff->fresh()->password))->toBeTrue();
});

test('manager cannot edit the owner', function () {
    $owner = User::factory()->owner()->create();
    $manager = User::factory()->manager()->create(['cafe_id' => $owner->cafe_id]);

    $this->actingAs($manager)->get("/admin/users/{$owner->id}/edit")->assertForbidden();
});
