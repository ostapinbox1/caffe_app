<?php

use App\Actions\PurchaseRequests\CreatePurchaseRequest;
use App\Enums\RequestStatus;
use App\Enums\Unit;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\PurchaseRequest;
use App\Models\RequestSection;
use App\Notifications\PurchaseRequestCreated;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('staff member creates a request from the phone form', function () {
    Notification::fake();
    $k = kitchen();
    $this->actingAs($k->staff);

    Livewire::test('pages::requests.create')
        ->call('chooseSection', $k->section->id)
        ->call('addItem', $k->flour->id)   // 1 кг
        ->call('increment', 0)             // +0,5 кг
        ->set('customName', 'Укроп')
        ->set('customUnit', Unit::Bunch->value)
        ->call('addCustom')
        ->set('isUrgent', true)
        ->set('comment', 'К выходным')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('requests.show', PurchaseRequest::first()));

    $request = PurchaseRequest::with('items')->first();

    expect($request->number)->toBe(1)
        ->and($request->status)->toBe(RequestStatus::New)
        ->and($request->is_urgent)->toBeTrue()
        ->and($request->comment)->toBe('К выходным')
        ->and($request->items)->toHaveCount(2)
        ->and($request->items[0]->name)->toBe('Мука')
        ->and((float) $request->items[0]->quantity)->toBe(1.5)
        ->and($request->items[1]->catalog_item_id)->toBeNull()
        ->and($request->items[1]->unit)->toBe(Unit::Bunch)
        ->and($request->events)->toHaveCount(1);

    Notification::assertSentTo($k->manager, PurchaseRequestCreated::class);
    Notification::assertNotSentTo($k->staff, PurchaseRequestCreated::class);
});

test('form shows what is already ordered in open requests', function () {
    $k = kitchen();
    app(CreatePurchaseRequest::class)->handle($k->staff, $k->section, [
        ['catalog_item_id' => $k->milk->id, 'unit' => 'l', 'quantity' => 6],
    ]);
    $this->actingAs($k->manager);

    Livewire::test('pages::requests.create')
        ->call('chooseSection', $k->section->id)
        ->assertSee('уже заказано — №1: 6 л, Новая');
});

test('quantity accepts a comma and an empty request is rejected', function () {
    $k = kitchen();
    $this->actingAs($k->staff);

    Livewire::test('pages::requests.create')
        ->call('chooseSection', $k->section->id)
        ->call('submit')
        ->assertHasErrors(['lines'])
        ->call('addItem', $k->milk->id)
        ->set('lines.0.quantity', '2,5')
        ->assertSet('lines.0.quantity', '2.5')
        ->call('submit')
        ->assertHasNoErrors();

    expect((float) PurchaseRequest::first()->items->first()->quantity)->toBe(2.5);
});

test('repeat opens the form with the same section and items', function () {
    $k = kitchen();
    $source = app(CreatePurchaseRequest::class)->handle($k->staff, $k->section, [
        ['catalog_item_id' => $k->flour->id, 'unit' => 'kg', 'quantity' => 3],
    ]);
    $this->actingAs($k->staff);

    Livewire::withQueryParams(['repeat' => $source->id])
        ->test('pages::requests.create')
        ->assertSet('sectionId', $k->section->id)
        ->assertSet('lines.0.name', 'Мука')
        ->assertSet('lines.0.quantity', 3.0);
});

test('numbers go in order within a cafe', function () {
    $k = kitchen();
    $other = kitchen();
    $create = app(CreatePurchaseRequest::class);
    $line = fn ($item) => [['catalog_item_id' => $item->id, 'unit' => 'kg', 'quantity' => 1]];

    $first = $create->handle($k->staff, $k->section, $line($k->flour));
    $second = $create->handle($k->staff, $k->section, $line($k->flour));
    $foreign = $create->handle($other->staff, $other->section, $line($other->flour));

    expect([$first->number, $second->number, $foreign->number])->toBe([1, 2, 1]);
});

test('same item with the same unit is merged, grams and kilograms stay separate', function () {
    $k = kitchen();

    $request = app(CreatePurchaseRequest::class)->handle($k->staff, $k->section, [
        ['catalog_item_id' => $k->flour->id, 'unit' => 'kg', 'quantity' => 1],
        ['catalog_item_id' => $k->flour->id, 'unit' => 'kg', 'quantity' => '0,5'],
        ['catalog_item_id' => $k->flour->id, 'unit' => 'g', 'quantity' => 300],
    ]);

    expect($request->items)->toHaveCount(2)
        ->and((float) $request->items[0]->quantity)->toBe(1.5)
        ->and($request->items[1]->unit)->toBe(Unit::Gram);
});

test('items from another section or cafe are rejected', function () {
    $k = kitchen();
    $bar = RequestSection::factory()->create(['cafe_id' => $k->cafe->id, 'name' => 'Бар']);
    $other = kitchen();

    expect(fn () => app(CreatePurchaseRequest::class)->handle($k->staff, $bar, [
        ['catalog_item_id' => $k->flour->id, 'unit' => 'kg', 'quantity' => 1],
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(CreatePurchaseRequest::class)->handle($k->staff, $k->section, [
        ['catalog_item_id' => $other->flour->id, 'unit' => 'kg', 'quantity' => 1],
    ]))->toThrow(ValidationException::class);

    expect(PurchaseRequest::withoutGlobalScopes()->count())->toBe(0);
});

test('hidden catalog items are not offered and cannot be added', function () {
    $k = kitchen();
    $category = CatalogCategory::withoutGlobalScopes()->first();
    $hidden = CatalogItem::factory()->hidden()->create(['catalog_category_id' => $category->id, 'name' => 'Старая позиция']);
    $this->actingAs($k->staff);

    $component = Livewire::test('pages::requests.create')
        ->call('chooseSection', $k->section->id)
        ->assertSee('Мука')
        ->assertDontSee('Старая позиция');

    $component->call('addItem', $hidden->id)->assertStatus(404);
});
