<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads_and_shows_the_app_name(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Our Little List');
    }

    public function test_guests_see_log_in_and_register_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('login'))
            ->assertSee(route('register'))
            ->assertSee('Log in')
            ->assertSee('Register');
    }

    public function test_signed_in_users_see_a_link_into_the_app(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('partner.search'))
            ->assertDontSee('Log in');
    }

    public function test_home_page_has_no_laravel_starter_content(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee("Let's get started")
            ->assertDontSee('Laracasts')
            ->assertDontSee('Deploy now');
    }
}
