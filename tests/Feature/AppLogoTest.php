<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppLogoTest extends TestCase
{
    use RefreshDatabase;

    private const LARAVEL_LOGO_PATH = 'M17.2 5.633';

    public function test_the_home_page_shows_the_new_logo(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-logo="our-little-list"', false)
            ->assertDontSee(self::LARAVEL_LOGO_PATH, false);
    }

    public function test_the_login_page_shows_the_new_logo(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-logo="our-little-list"', false)
            ->assertDontSee(self::LARAVEL_LOGO_PATH, false);
    }

    public function test_signed_in_pages_show_the_new_logo(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('partner.search'))
            ->assertOk()
            ->assertSee('data-logo="our-little-list"', false)
            ->assertDontSee(self::LARAVEL_LOGO_PATH, false);
    }

    public function test_each_logo_on_a_page_uses_a_unique_mask_id(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('partner.search'))
            ->assertOk()
            ->getContent();

        preg_match_all('/<mask id="([^"]+)"/', (string) $html, $matches);

        $this->assertGreaterThanOrEqual(2, count($matches[1]));
        $this->assertSame(count($matches[1]), count(array_unique($matches[1])));
    }

    public function test_the_svg_favicon_is_the_new_logo(): void
    {
        $svg = (string) file_get_contents(public_path('favicon.svg'));

        $this->assertStringNotContainsString('M162.041 38.7592', $svg);
        $this->assertStringContainsString('<mask', $svg);
        $this->assertStringContainsString('#262626', $svg);
    }

    public function test_the_ico_favicon_is_a_32px_png_icon(): void
    {
        $ico = (string) file_get_contents(public_path('favicon.ico'));

        $this->assertSame("\x00\x00\x01\x00", substr($ico, 0, 4));
        $this->assertSame(32, ord($ico[6]));
        $this->assertSame(32, ord($ico[7]));

        $offset = unpack('V', substr($ico, 18, 4))[1];

        $this->assertSame("\x89PNG", substr($ico, $offset, 4));
    }

    public function test_the_apple_touch_icon_is_a_180px_png(): void
    {
        $info = getimagesize(public_path('apple-touch-icon.png'));

        $this->assertNotFalse($info);
        $this->assertSame([180, 180, IMAGETYPE_PNG], [$info[0], $info[1], $info[2]]);
    }
}
