<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'a-valid-password';

    public function test_an_admin_is_created_with_a_valid_email_and_password(): void
    {
        $this->artisan('admin:create', ['email' => 'admin@example.com'])
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)
            ->assertSuccessful();

        $admin = Admin::query()->sole();

        $this->assertSame('admin@example.com', $admin->email);
        $this->assertTrue(Hash::check(self::PASSWORD, $admin->password));
        $this->assertSame(0, User::count());
    }

    public function test_the_email_is_trimmed_and_lowercased(): void
    {
        $this->artisan('admin:create', ['email' => '  Admin@Example.COM '])
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)
            ->assertSuccessful();

        $this->assertSame('admin@example.com', Admin::query()->sole()->email);
    }

    public function test_a_mismatched_confirmation_prompts_again(): void
    {
        $this->artisan('admin:create', ['email' => 'admin@example.com'])
            ->expectsQuestion('Password', 'first-password')
            ->expectsQuestion('Confirm password', 'something-else')
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)
            ->assertSuccessful();

        $this->assertTrue(Hash::check(self::PASSWORD, Admin::query()->sole()->password));
    }

    public function test_a_password_that_is_too_short_prompts_again(): void
    {
        $this->artisan('admin:create', ['email' => 'admin@example.com'])
            ->expectsQuestion('Password', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)
            ->assertSuccessful();

        $this->assertTrue(Hash::check(self::PASSWORD, Admin::query()->sole()->password));
    }

    public function test_an_empty_password_prompts_again(): void
    {
        $this->artisan('admin:create', ['email' => 'admin@example.com'])
            ->expectsQuestion('Password', '')
            ->expectsQuestion('Confirm password', '')
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)
            ->assertSuccessful();

        $this->assertSame(1, Admin::count());
    }

    public function test_an_email_already_used_by_an_admin_is_rejected_in_any_letter_case(): void
    {
        $existing = Admin::factory()->create(['email' => 'admin@example.com']);
        $hash = $existing->password;

        $this->artisan('admin:create', ['email' => 'ADMIN@example.com'])
            ->assertFailed();

        $this->assertSame(1, Admin::count());
        $this->assertSame($hash, $existing->fresh()->password);
    }

    public function test_a_malformed_email_is_rejected(): void
    {
        $this->artisan('admin:create', ['email' => 'not-an-email'])
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }

    public function test_an_email_over_255_characters_is_rejected(): void
    {
        $this->artisan('admin:create', ['email' => str_repeat('a', 250).'@example.com'])
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }

    public function test_it_fails_without_interactive_input(): void
    {
        $this->artisan('admin:create', ['email' => 'admin@example.com', '--no-interaction' => true])
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }

    public function test_an_email_used_by_a_regular_user_is_accepted_and_the_user_is_unaffected(): void
    {
        $user = User::factory()->create(['email' => 'shared@example.com']);
        $before = $user->fresh()->getAttributes();

        $this->artisan('admin:create', ['email' => 'shared@example.com'])
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)
            ->assertSuccessful();

        $this->assertSame(1, Admin::count());
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    public function test_the_password_cannot_be_passed_as_an_argument_or_option(): void
    {
        $definition = Artisan::all()['admin:create']->getDefinition();

        $this->assertTrue($definition->hasArgument('email'));
        $this->assertFalse($definition->hasArgument('password'));
        $this->assertFalse($definition->hasOption('password'));
    }
}
