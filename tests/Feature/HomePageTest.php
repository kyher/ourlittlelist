<?php

namespace Tests\Feature;

use App\Enums\ReviewCategory;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
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

    public function test_guests_see_log_in_and_sign_up_actions(): void
    {
        $html = $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('login'))
            ->assertSee('Log in')
            ->assertSee('Create your account')
            ->getContent();

        $this->assertGreaterThanOrEqual(2, substr_count((string) $html, 'href="'.route('register').'"'));
    }

    public function test_guests_see_what_the_app_is_and_how_it_works(): void
    {
        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-logo="our-little-list"', false)
            ->assertSee("Everything you've loved, together.")
            ->assertSee('Example')
            ->assertSee('Moonlight Over Marrow Lane')
            ->assertSee('Create your account.')
            ->assertSee('Connect with your partner.')
            ->assertSee('Review things together.')
            ->assertSee('Your reviews are visible only to you and your partner.');

        foreach (ReviewCategory::cases() as $category) {
            $response->assertSee($category->label());
        }
    }

    public function test_sign_up_actions_are_hidden_when_registration_is_unavailable(): void
    {
        config(['fortify.features' => array_values(array_diff(config('fortify.features'), [Features::registration()]))]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('login'))
            ->assertDontSee(route('register'));
    }

    public function test_the_home_page_shows_no_real_user_data(): void
    {
        $user = User::factory()->create(['name' => 'Zephyrine Quillfeather', 'username' => 'zephyrine_q']);
        Review::factory()->by($user)->create(['body' => 'An unmistakably distinctive review sentence.']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Zephyrine Quillfeather')
            ->assertDontSee('zephyrine_q')
            ->assertDontSee('An unmistakably distinctive review sentence.');
    }

    public function test_signed_in_users_see_a_link_into_the_app(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('register'))
            ->assertDontSee(route('login'))
            ->assertDontSee('Log in')
            ->getContent();

        $this->assertSame(3, substr_count((string) $html, 'href="'.route('partner.search').'"'));
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
