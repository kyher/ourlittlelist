<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('username', 'test_user')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test_user', $user->username);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_a_new_link_can_be_requested_from_the_profile_page(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')->call('resendVerificationNotification');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_requests_for_new_links_from_the_profile_page_are_limited(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $this->useUpVerificationLinkLimit($user);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->call('resendVerificationNotification')
            ->assertSee('Too many verification emails');

        Notification::assertNothingSent();
    }

    public function test_changing_email_sends_a_link_to_the_new_address(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('email', 'new@example.com')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_saving_with_the_same_email_sends_nothing(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('name', 'New Name')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_changing_email_over_the_limit_saves_and_sends_nothing(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $originalEmail = $user->email;
        $this->useUpVerificationLinkLimit($user);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('email', 'new@example.com')
            ->call('updateProfileInformation')
            ->assertHasErrors('email');

        $user->refresh();

        $this->assertSame($originalEmail, $user->email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
    }

    private function useUpVerificationLinkLimit(User $user): void
    {
        foreach (range(1, 6) as $attempt) {
            RateLimiter::hit('verification-link:'.$user->id, 60);
        }
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
    }

    public function test_profile_page_shows_the_current_username(): void
    {
        $user = User::factory()->create(['username' => 'kyle']);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')->assertSet('username', 'kyle');
    }

    public function test_username_can_be_changed_and_is_stored_lowercase(): void
    {
        $user = User::factory()->create(['username' => 'kyle']);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('username', ' Kyle_H ')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('kyle_h', $user->refresh()->username);
        $this->assertFalse(User::where('username', 'kyle')->exists());
    }

    public function test_username_cannot_be_changed_to_one_that_is_already_taken(): void
    {
        User::factory()->create(['username' => 'harry']);
        $user = User::factory()->create(['username' => 'kyle']);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('username', 'Harry')
            ->call('updateProfileInformation')
            ->assertHasErrors('username');

        $this->assertSame('kyle', $user->refresh()->username);
    }

    public function test_saving_with_an_unchanged_username_succeeds(): void
    {
        $user = User::factory()->create(['username' => 'kyle']);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('name', 'Kyle Renamed')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('Kyle Renamed', $user->refresh()->name);
    }

    public function test_username_cannot_be_changed_to_a_reserved_word(): void
    {
        $user = User::factory()->create(['username' => 'kyle']);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('username', 'settings')
            ->call('updateProfileInformation')
            ->assertHasErrors('username');

        $this->assertSame('kyle', $user->refresh()->username);
    }
}
