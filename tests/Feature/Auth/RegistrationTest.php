<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'username' => 'johndoe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('newly registered users land on the dashboard without verifying their email', function () {
    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'username' => 'janedoe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->get(route('dashboard'))->assertOk();
});

test('users cannot register with an email that is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Someone Else',
        'username' => 'someoneelse',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Harry',
        'username' => 'harry',
        'email' => 'harry@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

test('registration screen has a username field', function () {
    $this->get(route('register'))->assertSee('name="username"', false);
});

test('usernames are stored trimmed and lowercase', function () {
    $this->post(route('register.store'), registrationPayload(['username' => '  Harry ']))
        ->assertSessionHasNoErrors();

    expect(User::where('email', 'harry@example.com')->value('username'))->toBe('harry');
});

test('usernames must be unique regardless of case', function () {
    User::factory()->create(['username' => 'harry']);

    $this->post(route('register.store'), registrationPayload(['username' => 'HARRY']))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
    expect(User::where('username', 'harry')->count())->toBe(1);
});

test('usernames must follow the format rules', function (string $username) {
    $this->post(route('register.store'), registrationPayload(['username' => $username]))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
})->with([
    'too short' => 'ab',
    'too long' => str_repeat('a', 31),
    'leading underscore' => '_harry',
    'contains a space' => 'har ry',
    'contains a hyphen' => 'har-ry',
]);

test('reserved usernames cannot be registered', function () {
    $this->post(route('register.store'), registrationPayload(['username' => 'Admin']))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('a username is required to register', function () {
    $this->post(route('register.store'), registrationPayload(['username' => '']))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});
