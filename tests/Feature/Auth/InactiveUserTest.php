<?php

use App\Models\User;

test('inactive user cannot log in', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('user switched off during a session is logged out', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->update(['is_active' => false]);

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('manager cannot edit the owner in the admin panel', function () {
    $owner = User::factory()->owner()->create();
    $manager = User::factory()->manager()->create(['cafe_id' => $owner->cafe_id]);

    $this->actingAs($manager)->get("/admin/users/{$owner->id}/edit")->assertForbidden();
});
