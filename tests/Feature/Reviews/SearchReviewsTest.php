<?php

namespace Tests\Feature\Reviews;

use App\Actions\Reviews\CreateReview;
use App\Enums\ReviewCategory;
use App\Models\Couple;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class SearchReviewsTest extends TestCase
{
    use RefreshDatabase;

    private User $kyle;

    private User $harry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kyle = User::factory()->create(['name' => 'Kyle', 'username' => 'kyle']);
        $this->harry = User::factory()->create(['name' => 'Harry', 'username' => 'harry']);

        Couple::factory()->withMembers($this->kyle, $this->harry)->create();
        $this->kyle->refresh();
        $this->harry->refresh();

        $this->review($this->kyle, ReviewCategory::Film, 'The Grand Budapest Hotel', '3.5');
        $this->review($this->kyle, ReviewCategory::Film, 'Paddington 2', '5');
        $this->review($this->kyle, ReviewCategory::Food, '100%_Juice', '2');
        $this->review($this->kyle, ReviewCategory::Restaurant, 'Juice bar', '4');
    }

    private function review(User $user, ReviewCategory $category, string $title, string $stars): Review
    {
        return app(CreateReview::class)($user, null, $category, $title, $stars, 'Some thoughts.')[0];
    }

    /**
     * @return list<string>
     */
    private function search(string $search): array
    {
        $component = Livewire::actingAs($this->kyle)->test('pages::reviews.index')->set('search', $search);

        return $this->titles($component);
    }

    /**
     * @return list<string>
     */
    private function titles(Testable $component): array
    {
        return array_column($component->instance()->items, 'title');
    }

    public function test_search_box_is_shown(): void
    {
        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->assertSeeHtml('data-test="review-search"');
    }

    public function test_search_matches_part_of_the_title(): void
    {
        $this->assertSame(['The Grand Budapest Hotel'], $this->search('budapest'));
    }

    public function test_search_ignores_case_and_surrounding_spaces(): void
    {
        $this->assertSame(['The Grand Budapest Hotel'], $this->search('BUDAPEST'));
        $this->assertSame(['The Grand Budapest Hotel'], $this->search('  budapest  '));
    }

    public function test_percent_and_underscore_are_matched_literally(): void
    {
        $this->assertSame(['100%_Juice'], $this->search('%_'));
        $this->assertSame(['100%_Juice'], $this->search('_'));
        $this->assertSame(['100%_Juice'], $this->search('%'));
    }

    public function test_exclamation_mark_is_matched_literally(): void
    {
        $this->review($this->kyle, ReviewCategory::Book, 'Wow!', '4');

        $this->assertSame(['Wow!'], $this->search('!'));
    }

    public function test_clearing_search_restores_the_list(): void
    {
        $component = Livewire::actingAs($this->kyle)->test('pages::reviews.index')->set('search', 'budapest');
        $this->assertCount(1, $this->titles($component));

        $component->set('search', '');
        $this->assertCount(4, $this->titles($component));
    }

    public function test_spaces_only_search_is_ignored(): void
    {
        $this->assertCount(4, $this->search('   '));
    }

    public function test_search_is_cut_to_100_characters(): void
    {
        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->set('search', str_repeat('a', 150))
            ->assertOk()
            ->assertSeeHtml('data-test="reviews-no-matches"');
    }

    public function test_search_combines_with_category(): void
    {
        $component = Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->set('search', 'juice')
            ->set('category', 'food');

        $this->assertSame(['100%_Juice'], $this->titles($component));
    }

    public function test_search_never_shows_another_couples_items(): void
    {
        $alex = User::factory()->create(['username' => 'alex']);
        $sam = User::factory()->create(['username' => 'sam']);
        Couple::factory()->withMembers($alex, $sam)->create();
        $this->review($alex->refresh(), ReviewCategory::Film, 'Paddington 2', '1');

        $component = Livewire::actingAs($this->kyle)->test('pages::reviews.index')->set('search', 'paddington');
        $items = $component->instance()->items;

        $this->assertCount(1, $items);
        $this->assertSame($this->kyle->couple_id, Item::findOrFail($items[0]['id'])->couple_id);
    }

    public function test_search_is_kept_in_the_query_string(): void
    {
        $component = Livewire::withQueryParams(['q' => 'budapest'])
            ->actingAs($this->kyle)
            ->test('pages::reviews.index')
            ->assertSet('search', 'budapest');

        $this->assertSame(['The Grand Budapest Hotel'], $this->titles($component));
    }

    public function test_no_matches_message_for_unmatched_search(): void
    {
        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->set('search', 'zzz')
            ->assertSeeHtml('data-test="reviews-no-matches"')
            ->assertSee('Nothing matches your search and filters.');
    }
}
