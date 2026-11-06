<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Sam Example',
            'email' => 'sam@example.com',
        ]);
    }

    public function test_the_navigation_links_to_find_your_partner(): void
    {
        $this->actingAs($this->user)
            ->get(route('partner.search'))
            ->assertOk()
            ->assertSee('Find your partner')
            ->assertSee(route('partner.search'));
    }

    public function test_the_user_menu_shows_the_user_settings_and_log_out(): void
    {
        $this->actingAs($this->user)
            ->get(route('partner.search'))
            ->assertOk()
            ->assertSee('Sam Example')
            ->assertSee('sam@example.com')
            ->assertSee('Settings')
            ->assertSee(route('profile.edit'))
            ->assertSee('Log out')
            ->assertSee('data-test="logout-button"', false);
    }

    public function test_the_navigation_has_no_starter_kit_links(): void
    {
        $this->actingAs($this->user)
            ->get(route('partner.search'))
            ->assertOk()
            ->assertDontSee('Repository')
            ->assertDontSee('Documentation')
            ->assertDontSee('github.com/laravel/livewire-starter-kit')
            ->assertDontSee('laravel.com/docs/starter-kits');
    }

    public function test_the_sidebar_profile_button_is_gone(): void
    {
        $this->actingAs($this->user)
            ->get(route('partner.search'))
            ->assertOk()
            ->assertDontSee('data-test="sidebar-menu-button"', false);
    }

    public function test_the_navigation_has_no_dashboard_item(): void
    {
        $this->actingAs($this->user)
            ->get(route('partner.search'))
            ->assertOk()
            ->assertDontSee('Dashboard');
    }

    public function test_the_old_dashboard_address_returns_not_found(): void
    {
        $this->get('/dashboard')->assertNotFound();

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertNotFound();
    }

    public function test_login_still_honours_the_intended_url(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $this->user->email,
            'password' => 'password',
        ])->assertRedirect(route('profile.edit', absolute: false));
    }

    public function test_settings_pages_use_the_same_navigation(): void
    {
        $this->actingAs($this->user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Find your partner')
            ->assertSee('Log out');
    }
}
