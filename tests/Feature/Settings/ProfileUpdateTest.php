<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs(User::factory()->create(['email' => 'anna@cafe.test']));

    $this->get(route('profile.edit'))->assertOk()->assertSee('anna@cafe.test');
});

test('employee changes own name, login stays', function () {
    $user = User::factory()->create(['email' => 'anna@cafe.test']);
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('lastName', 'Остапенко')
        ->set('firstName', 'Владислав')
        ->set('middleName', 'Сергеевич')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Владислав Остапенко')
        ->and($user->fullName())->toBe('Остапенко Владислав Сергеевич')
        ->and($user->shortName())->toBe('Владислав О.')
        ->and($user->greetingName())->toBe('Владислав')
        ->and($user->email)->toBe('anna@cafe.test');
});

test('first name is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.profile')
        ->set('firstName', '')
        ->call('updateProfileInformation')
        ->assertHasErrors('firstName');
});

test('avatar is uploaded and shrunk to a square', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('avatar', UploadedFile::fake()->image('me.jpg', 1200, 800))
        ->assertHasNoErrors();

    $path = $user->fresh()->avatar_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
    expect(getimagesizefromstring(Storage::disk('public')->get($path))[0])->toBe(256);

    Livewire::test('pages::settings.profile')->call('removeAvatar');
    Storage::disk('public')->assertMissing($path);
    expect($user->fresh()->avatar_path)->toBeNull();
});
