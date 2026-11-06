<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Harry',
            'username' => 'harry',
            'email' => 'harry@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
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
    }

    public function test_newly_registered_users_land_on_the_dashboard_without_verifying_their_email(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_users_cannot_register_with_an_email_that_is_already_taken(): void
    {
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
        $this->assertSame(1, User::where('email', 'taken@example.com')->count());
    }

    public function test_registration_screen_has_a_username_field(): void
    {
        $this->get(route('register'))->assertSee('name="username"', false);
    }

    public function test_usernames_are_stored_trimmed_and_lowercase(): void
    {
        $this->post(route('register.store'), $this->registrationPayload(['username' => '  Harry ']))
            ->assertSessionHasNoErrors();

        $this->assertSame('harry', User::where('email', 'harry@example.com')->value('username'));
    }

    public function test_usernames_must_be_unique_regardless_of_case(): void
    {
        User::factory()->create(['username' => 'harry']);

        $this->post(route('register.store'), $this->registrationPayload(['username' => 'HARRY']))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->assertSame(1, User::where('username', 'harry')->count());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function badlyFormattedUsernames(): array
    {
        return [
            'too short' => ['ab'],
            'too long' => [str_repeat('a', 31)],
            'leading underscore' => ['_harry'],
            'contains a space' => ['har ry'],
            'contains a hyphen' => ['har-ry'],
        ];
    }

    #[DataProvider('badlyFormattedUsernames')]
    public function test_usernames_must_follow_the_format_rules(string $username): void
    {
        $this->post(route('register.store'), $this->registrationPayload(['username' => $username]))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_reserved_usernames_cannot_be_registered(): void
    {
        $this->post(route('register.store'), $this->registrationPayload(['username' => 'Admin']))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_a_username_is_required_to_register(): void
    {
        $this->post(route('register.store'), $this->registrationPayload(['username' => '']))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
