<?php

use App\Models\User;
use Livewire\Livewire;

test('users without a username are redirected to choose one', function (string $route) {
    $user = User::factory()->withoutUsername()->create();

    $this->actingAs($user)
        ->get(route($route))
        ->assertRedirect(route('username.create'));
})->with(['dashboard', 'profile.edit', 'partner.search', 'appearance.edit']);

test('users without a username can view the choose username page', function () {
    $user = User::factory()->withoutUsername()->create();

    $this->actingAs($user)
        ->get(route('username.create'))
        ->assertOk()
        ->assertSee('Choose a username');
});

test('users can choose a username and continue to the dashboard', function () {
    $user = User::factory()->withoutUsername()->create();

    $this->actingAs($user);

    Livewire::test('pages::auth.choose-username')
        ->set('username', ' Kyle ')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    expect($user->refresh()->username)->toBe('kyle');

    $this->get(route('dashboard'))->assertOk();
});

test('users are returned to the page they originally asked for', function () {
    $user = User::factory()->withoutUsername()->create();

    $this->actingAs($user)->get(route('partner.search'));

    Livewire::test('pages::auth.choose-username')
        ->set('username', 'kyle')
        ->call('save')
        ->assertRedirect(route('partner.search'));
});

test('chosen usernames are validated', function (string $username) {
    User::factory()->create(['username' => 'harry']);
    $user = User::factory()->withoutUsername()->create();

    $this->actingAs($user);

    Livewire::test('pages::auth.choose-username')
        ->set('username', $username)
        ->call('save')
        ->assertHasErrors('username');

    expect($user->refresh()->username)->toBeNull();
})->with([
    'empty' => '',
    'too short' => 'ab',
    'invalid characters' => 'kyle!',
    'reserved' => 'admin',
    'taken in another case' => 'HARRY',
]);

test('users who already have a username are sent to the dashboard', function () {
    $user = User::factory()->create(['username' => 'kyle']);

    $this->actingAs($user)
        ->get(route('username.create'))
        ->assertRedirect(route('dashboard'));
});

test('users without a username can still log out', function () {
    $user = User::factory()->withoutUsername()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});

test('guests cannot view the choose username page', function () {
    $this->get(route('username.create'))->assertRedirect(route('login'));
});
