<?php

use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('username', 'test_user')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->username)->toEqual('test_user');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('profile page shows the current username', function () {
    $user = User::factory()->create(['username' => 'kyle']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')->assertSet('username', 'kyle');
});

test('username can be changed and is stored lowercase', function () {
    $user = User::factory()->create(['username' => 'kyle']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('username', ' Kyle_H ')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->username)->toBe('kyle_h');
    expect(User::where('username', 'kyle')->exists())->toBeFalse();
});

test('username cannot be changed to one that is already taken', function () {
    User::factory()->create(['username' => 'harry']);
    $user = User::factory()->create(['username' => 'kyle']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('username', 'Harry')
        ->call('updateProfileInformation')
        ->assertHasErrors('username');

    expect($user->refresh()->username)->toBe('kyle');
});

test('saving with an unchanged username succeeds', function () {
    $user = User::factory()->create(['username' => 'kyle']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('name', 'Kyle Renamed')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->name)->toBe('Kyle Renamed');
});

test('username cannot be changed to a reserved word', function () {
    $user = User::factory()->create(['username' => 'kyle']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('username', 'settings')
        ->call('updateProfileInformation')
        ->assertHasErrors('username');

    expect($user->refresh()->username)->toBe('kyle');
});
