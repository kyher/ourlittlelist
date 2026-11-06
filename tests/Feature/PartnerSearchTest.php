<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PartnerSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $kyle;

    private User $harry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kyle = User::factory()->create(['name' => 'Kyle', 'username' => 'kyle', 'email' => 'kyle@example.com']);
        $this->harry = User::factory()->create(['name' => 'Harry', 'username' => 'harry', 'email' => 'harry@example.com']);

        RateLimiter::clear('partner-search:'.$this->kyle->id);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('partner.search'))->assertRedirect(route('login'));
    }

    public function test_signed_in_users_can_view_the_partner_search_page(): void
    {
        $this->actingAs($this->kyle)
            ->get(route('partner.search'))
            ->assertOk()
            ->assertSee('Find your partner');
    }

    public function test_a_user_can_find_their_partner_by_exact_username_in_any_case(): void
    {
        $this->actingAs($this->kyle);

        Livewire::test('pages::partner.search')
            ->set('query', 'HARRY')
            ->call('search')
            ->assertHasNoErrors()
            ->assertSee('@harry')
            ->assertSee('Harry')
            ->assertSet('result', ['username' => 'harry', 'name' => 'Harry']);
    }

    public function test_search_results_never_expose_the_email_address(): void
    {
        $this->actingAs($this->kyle);

        $component = Livewire::test('pages::partner.search')
            ->set('query', 'harry')
            ->call('search')
            ->assertDontSee('harry@example.com');

        $this->assertStringNotContainsString('harry@example.com', (string) json_encode($component->get('result')));
    }

    public function test_surrounding_whitespace_in_the_search_term_is_ignored(): void
    {
        $this->actingAs($this->kyle);

        Livewire::test('pages::partner.search')
            ->set('query', '  harry  ')
            ->call('search')
            ->assertSee('@harry');
    }

    public function test_partial_usernames_do_not_match(): void
    {
        $this->actingAs($this->kyle);

        Livewire::test('pages::partner.search')
            ->set('query', 'har')
            ->call('search')
            ->assertSet('result', null)
            ->assertSee('No user found with that username.');
    }

    public function test_searching_for_your_own_username_returns_no_result(): void
    {
        $this->actingAs($this->kyle);

        Livewire::test('pages::partner.search')
            ->set('query', 'kyle')
            ->call('search')
            ->assertSet('result', null)
            ->assertSee('No user found with that username.');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function emptyQueries(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
        ];
    }

    #[DataProvider('emptyQueries')]
    public function test_an_empty_search_term_is_rejected_without_searching(string $query): void
    {
        $this->actingAs($this->kyle);

        Livewire::test('pages::partner.search')
            ->set('query', $query)
            ->call('search')
            ->assertHasErrors('query')
            ->assertSet('searched', false);
    }

    public function test_searches_are_rate_limited_per_user(): void
    {
        $this->actingAs($this->kyle);

        $component = Livewire::test('pages::partner.search')->set('query', 'nobody');

        foreach (range(1, 30) as $ignored) {
            $component->call('search')->assertHasNoErrors();
        }

        $component->set('query', 'harry')
            ->call('search')
            ->assertHasErrors('query')
            ->assertSee('Too many searches')
            ->assertSet('result', null);
    }
}
