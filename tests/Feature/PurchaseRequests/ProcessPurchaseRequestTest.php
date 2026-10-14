<?php

use App\Actions\PurchaseRequests\CreatePurchaseRequest;
use App\Enums\RequestStatus;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\ViewPurchaseRequest;
use App\Filament\Widgets\RequestsOverview;
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

    $this->get(route('dashboard'))->assertOk()->assertSee('Мои открытые заявки')->assertSee('№1 · Кухня');
    $this->get(route('requests.index'))->assertOk()->assertSee('Мука 2 кг');
    $this->get(route('requests.index', ['tab' => 'closed']))->assertOk()->assertSee('Заявок нет');
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

test('admin panel is for managers only', function () {
    $k = kitchen();
    $request = newRequest($k);

    $this->actingAs($k->staff)->get('/admin')->assertForbidden();

    $this->actingAs($k->manager);
    $this->get('/admin')->assertOk();
    Livewire::test(RequestsOverview::class)->assertSee('Новые заявки');
    $this->get('/admin/purchase-requests')->assertOk()->assertSee('Мука 2 кг');
    $this->get("/admin/purchase-requests/{$request->id}")->assertOk()->assertSee('Анна Бариста');
    $this->get("/admin/purchase-requests/{$request->id}/edit")->assertOk();
    $this->get('/admin/catalog-items')->assertOk()->assertSee('Молоко');
    $this->get('/admin/catalog-categories')->assertOk();
    $this->get('/admin/request-sections')->assertOk();
    $this->get('/admin/users')->assertOk()->assertSee('Анна Бариста');
});

test('manager handles a request in the admin panel', function () {
    $k = kitchen();
    $request = newRequest($k);
    $this->actingAs($k->manager);

    Livewire::test(ViewPurchaseRequest::class, ['record' => $request->getRouteKey()])
        ->callAction('accept');
    expect($request->refresh()->status)->toBe(RequestStatus::Accepted);

    Livewire::test(EditPurchaseRequest::class, ['record' => $request->getRouteKey()])
        ->fillForm(['status' => RequestStatus::Ordered->value, 'manager_comment' => 'Заказал у поставщика'])
        ->call('save')
        ->assertHasNoFormErrors();

    $request->refresh();
    expect($request->status)->toBe(RequestStatus::Ordered)
        ->and($request->manager_comment)->toBe('Заказал у поставщика')
        ->and($k->staff->notifications()->count())->toBe(2);
});

test('manager of another cafe cannot open the request in the admin panel', function () {
    $k = kitchen();
    $other = kitchen();
    $request = newRequest($k);

    $this->actingAs($other->manager)->get("/admin/purchase-requests/{$request->id}")->assertNotFound();
});
