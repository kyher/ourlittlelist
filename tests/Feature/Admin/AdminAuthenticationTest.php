<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Tests that cross between the two logins sign in through the form. actingAs($admin, 'admin')
 * would make "admin" the default guard, which the regular auth middleware would then accept.
 */
class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function logInAsAdmin(string $email, string $password = 'password'): TestResponse
    {
        return $this->post(route('admin.login.store'), [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function test_the_login_page_renders(): void
    {
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_an_admin_can_log_in(): void
    {
        $admin = Admin::factory()->create();

        $this->logInAsAdmin($admin->email)->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_the_email_matches_in_any_letter_case(): void
    {
        $admin = Admin::factory()->create(['email' => 'admin@example.com']);

        $this->logInAsAdmin('ADMIN@Example.com')->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_a_wrong_password_is_rejected_with_the_generic_message(): void
    {
        $admin = Admin::factory()->create();

        $this->logInAsAdmin($admin->email, 'wrong-password')
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest('admin');
    }

    public function test_an_unknown_email_is_rejected_with_the_generic_message(): void
    {
        $this->logInAsAdmin('nobody@example.com')
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest('admin');
    }

    public function test_regular_user_credentials_are_rejected_at_the_admin_login(): void
    {
        $user = User::factory()->create();

        $this->logInAsAdmin($user->email)
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest('admin');
        $this->assertGuest('web');
    }

    public function test_admin_credentials_are_rejected_at_the_regular_login(): void
    {
        $admin = Admin::factory()->create();

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
        $this->assertGuest('admin');
    }

    public function test_a_shared_email_signs_in_only_at_the_login_used(): void
    {
        User::factory()->create(['email' => 'shared@example.com']);
        Admin::factory()->create(['email' => 'shared@example.com']);

        $this->logInAsAdmin('shared@example.com')->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated('admin');
        $this->assertGuest('web');
    }

    public function test_a_guest_is_sent_to_the_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_a_regular_user_is_sent_to_the_admin_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_a_signed_in_admin_has_no_access_to_regular_pages(): void
    {
        $admin = Admin::factory()->create();
        $this->logInAsAdmin($admin->email);

        $this->get(route('reviews.index'))->assertRedirect(route('login'));
        $this->get(route('couple.show'))->assertRedirect(route('login'));
    }

    public function test_a_signed_in_admin_skips_the_login_page(): void
    {
        $admin = Admin::factory()->create();
        $this->logInAsAdmin($admin->email);

        $this->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_admin_can_log_out(): void
    {
        $admin = Admin::factory()->create();
        $this->logInAsAdmin($admin->email);

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_repeated_failed_logins_are_throttled(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->logInAsAdmin('admin@example.com', 'wrong-password')->assertRedirect();
        }

        $this->logInAsAdmin('admin@example.com', 'wrong-password')->assertTooManyRequests();
    }

    public function test_a_removed_admin_is_sent_to_the_login_page(): void
    {
        $admin = Admin::factory()->create();
        $this->logInAsAdmin($admin->email);

        Admin::query()->delete();
        $this->app['auth']->forgetGuards();

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_paths_use_their_own_session_cookie(): void
    {
        $this->get(route('admin.login'));
        $this->assertSame(config('session.cookie').'-admin', app('session.store')->getName());

        $this->get(route('login'));
        $this->assertSame(config('session.cookie'), app('session.store')->getName());
    }
}
