<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChooseUsernameTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function gatedRoutes(): array
    {
        return [
            'profile.edit' => ['profile.edit'],
            'partner.search' => ['partner.search'],
            'appearance.edit' => ['appearance.edit'],
        ];
    }

    #[DataProvider('gatedRoutes')]
    public function test_users_without_a_username_are_redirected_to_choose_one(string $route): void
    {
        $user = User::factory()->withoutUsername()->create();

        $this->actingAs($user)
            ->get(route($route))
            ->assertRedirect(route('username.create'));
    }

    public function test_users_without_a_username_can_view_the_choose_username_page(): void
    {
        $user = User::factory()->withoutUsername()->create();

        $this->actingAs($user)
            ->get(route('username.create'))
            ->assertOk()
            ->assertSee('Choose a username');
    }

    public function test_users_can_choose_a_username_and_continue_to_partner_search(): void
    {
        $user = User::factory()->withoutUsername()->create();

        $this->actingAs($user);

        Livewire::test('pages::auth.choose-username')
            ->set('username', ' Kyle ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.search', absolute: false));

        $this->assertSame('kyle', $user->refresh()->username);

        $this->get(route('partner.search'))->assertOk();
    }

    public function test_users_are_returned_to_the_page_they_originally_asked_for(): void
    {
        $user = User::factory()->withoutUsername()->create();

        $this->actingAs($user)->get(route('partner.search'));

        Livewire::test('pages::auth.choose-username')
            ->set('username', 'kyle')
            ->call('save')
            ->assertRedirect(route('partner.search'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidUsernames(): array
    {
        return [
            'empty' => [''],
            'too short' => ['ab'],
            'invalid characters' => ['kyle!'],
            'reserved' => ['admin'],
            'taken in another case' => ['HARRY'],
        ];
    }

    #[DataProvider('invalidUsernames')]
    public function test_chosen_usernames_are_validated(string $username): void
    {
        User::factory()->create(['username' => 'harry']);
        $user = User::factory()->withoutUsername()->create();

        $this->actingAs($user);

        Livewire::test('pages::auth.choose-username')
            ->set('username', $username)
            ->call('save')
            ->assertHasErrors('username');

        $this->assertNull($user->refresh()->username);
    }

    public function test_users_who_already_have_a_username_are_sent_to_partner_search(): void
    {
        $user = User::factory()->create(['username' => 'kyle']);

        $this->actingAs($user)
            ->get(route('username.create'))
            ->assertRedirect(route('partner.search'));
    }

    public function test_users_without_a_username_can_still_log_out(): void
    {
        $user = User::factory()->withoutUsername()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_guests_cannot_view_the_choose_username_page(): void
    {
        $this->get(route('username.create'))->assertRedirect(route('login'));
    }
}
