<?php

use App\Actions\PurchaseRequests\CreatePurchaseRequest;
use App\Enums\RequestStatus;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PurchaseRequestCancelled;
use App\Notifications\PurchaseRequestUpdated;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function newRequest(object $k): PurchaseRequest
{
    return app(CreatePurchaseRequest::class)->handle($k->staff, $k->section, [
        ['catalog_item_id' => $k->flour->id, 'unit' => 'kg', 'quantity' => 2],
    ]);
}

test('manager changes status and answers, author is notified once', function () {
    $k = kitchen();
    $request = newRequest($k);
    Notification::fake();

    $request->updateByManager($k->manager, [
        'status' => RequestStatus::Ordered,
        'manager_comment' => 'Привезут в четверг',
        'expected_at' => now()->addDays(2)->toDateString(),
    ]);

    $request->refresh();
    expect($request->status)->toBe(RequestStatus::Ordered)
        ->and($request->manager_comment)->toBe('Привезут в четверг')
        ->and($request->events()->count())->toBe(4); // создана, статус, ответ, дата

    Notification::assertSentToTimes($k->staff, PurchaseRequestUpdated::class, 1);

    // Ничего не поменялось — ни истории, ни уведомления.
    $request->updateByManager($k->manager, [
        'status' => RequestStatus::Ordered,
        'manager_comment' => 'Привезут в четверг',
        'expected_at' => $request->expected_at->toDateString(),
    ]);
    expect($request->events()->count())->toBe(4);
    Notification::assertSentToTimes($k->staff, PurchaseRequestUpdated::class, 1);
});

test('author cancels own new request, managers are notified', function () {
    $k = kitchen();
    $request = newRequest($k);
    Notification::fake();
    $this->actingAs($k->staff);

    Livewire::test('pages::requests.show', ['purchaseRequest' => $request])
        ->call('cancel')
        ->assertHasNoErrors();

    expect($request->refresh()->status)->toBe(RequestStatus::Cancelled);
    Notification::assertSentTo($k->manager, PurchaseRequestCancelled::class);
});

test('request cannot be cancelled by someone else or after it was accepted', function () {
    $k = kitchen();
    $request = newRequest($k);
    $colleague = User::factory()->create(['cafe_id' => $k->cafe->id]);

    expect($request->canBeCancelledBy($colleague))->toBeFalse();

    $request->updateByManager($k->manager, ['status' => RequestStatus::Accepted]);

    expect($request->refresh()->canBeCancelledBy($k->staff))->toBeFalse()
        ->and(fn () => $request->cancelBy($k->staff))->toThrow(HttpException::class);
});

test('staff sees requests of own cafe only', function () {
    $k = kitchen();
    $other = kitchen();
    $request = newRequest($k);

    $this->actingAs($k->staff)->get(route('requests.show', $request))->assertOk()->assertSee('Мука');
    $this->actingAs($other->staff)->get(route('requests.show', $request))->assertNotFound();
    $this->actingAs($other->staff)->get(route('requests.index'))->assertOk()->assertDontSee('№1 · Кухня');
});

test('staff pages render', function () {
    $k = kitchen();
    newRequest($k);
    $this->actingAs($k->staff);

    $this->get(route('dashboard'))->assertOk()->assertSee('Мои заявки')->assertSee('№1 · Кухня');
    $this->get(route('requests.index'))->assertOk()->assertSee('Мука 2 кг');
    $this->get(route('requests.index', ['mine' => 1]))->assertOk()->assertSee('Мука 2 кг');
    $this->get(route('requests.create'))->assertOk()->assertSee('Кухня');
    $this->get(route('notifications'))->assertOk();
});

test('opening a notification marks it read and leads to the request', function () {
    $k = kitchen();
    $request = newRequest($k);
    $request->updateByManager($k->manager, ['status' => RequestStatus::Accepted]);
    $this->actingAs($k->staff);

    $notification = $k->staff->unreadNotifications()->first();
    expect($notification->data['title'])->toBe('Заявка №1: Принята');

    Livewire::test('pages::notifications')
        ->assertSee('Заявка №1: Принята')
        ->call('open', $notification->id)
        ->assertRedirect(route('requests.show', $request));

    expect($k->staff->unreadNotifications()->count())->toBe(0);
});

test('manager processes a request right in the cabinet', function () {
    $k = kitchen();
    $request = newRequest($k);
    $this->actingAs($k->manager);

    Livewire::test('pages::requests.show', ['purchaseRequest' => $request])
        ->assertSee('Обработка')
        ->call('advance')
        ->assertSee('Принята')
        ->set('status', RequestStatus::Ordered->value)
        ->set('managerComment', 'Заказал у поставщика')
        ->set('expectedAt', now()->addDay()->toDateString())
        ->call('saveAnswer')
        ->assertHasNoErrors();

    $request->refresh();
    expect($request->status)->toBe(RequestStatus::Ordered)
        ->and($request->manager_comment)->toBe('Заказал у поставщика')
        ->and($request->expected_at->isTomorrow())->toBeTrue()
        ->and($k->staff->notifications()->count())->toBe(2);
});

test('staff cannot process requests', function () {
    $k = kitchen();
    $request = newRequest($k);
    $this->actingAs($k->staff);

    Livewire::test('pages::requests.show', ['purchaseRequest' => $request])
        ->assertDontSee('Обработка')
        ->call('advance')
        ->assertForbidden();

    expect($request->refresh()->status)->toBe(RequestStatus::New);
});

test('requests board groups requests by stage', function () {
    $k = kitchen();
    $accepted = newRequest($k);
    $accepted->updateByManager($k->manager, ['status' => RequestStatus::Accepted]);
    newRequest($k);
    $this->actingAs($k->staff);

    $board = Livewire::test('pages::requests.index')->instance()->board;

    expect($board->get('new'))->toHaveCount(1)
        ->and($board->get('accepted'))->toHaveCount(1);
});
