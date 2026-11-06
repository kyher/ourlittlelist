<?php

namespace Tests\Feature;

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
}
