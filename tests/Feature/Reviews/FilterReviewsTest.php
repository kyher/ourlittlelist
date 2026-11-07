<?php

namespace Tests\Feature\Reviews;

use App\Actions\Reviews\CreateReview;
use App\Actions\Reviews\DismissReviewPrompt;
use App\Enums\ReviewCategory;
use App\Models\Couple;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class FilterReviewsTest extends TestCase
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
    }

    private function review(User $user, ReviewCategory $category, string $title, string $stars): Review
    {
        return app(CreateReview::class)($user, null, $category, $title, $stars, 'Some thoughts.')[0];
    }

    private function alsoReview(User $user, Item $item, string $stars): Review
    {
        return app(CreateReview::class)($user, $item, null, null, $stars, 'Some thoughts.')[0];
    }

    /**
     * "Both" (Kyle 4, Harry 3), "Kyle only", "Harry only" (a Book) and "Harry dismissed",
     * whose prompt Kyle has dismissed.
     */
    private function seedItems(): void
    {
        $this->alsoReview($this->harry, $this->review($this->kyle, ReviewCategory::Film, 'Both', '4')->item, '3');
        $this->review($this->kyle, ReviewCategory::Film, 'Kyle only', '5');
        $this->review($this->harry, ReviewCategory::Book, 'Harry only', '2');
        $dismissed = $this->review($this->harry, ReviewCategory::Film, 'Harry dismissed', '4')->item;

        app(DismissReviewPrompt::class)($this->kyle->reviewPrompts()->where('item_id', $dismissed->id)->firstOrFail());
    }

    /**
     * @return list<string>
     */
    private function titles(Testable $component): array
    {
        return array_column($component->instance()->items, 'title');
    }

    public function test_all_shows_every_item(): void
    {
        $this->seedItems();

        $component = Livewire::actingAs($this->kyle)->test('pages::reviews.index');

        $this->assertEqualsCanonicalizing(['Both', 'Kyle only', 'Harry only', 'Harry dismissed'], $this->titles($component));
    }

    public function test_reviewed_by_me(): void
    {
        $this->seedItems();

        $component = Livewire::actingAs($this->kyle)->test('pages::reviews.index')->set('show', 'mine');

        $this->assertEqualsCanonicalizing(['Both', 'Kyle only'], $this->titles($component));
    }

    public function test_reviewed_by_partner(): void
    {
        $this->seedItems();

        $component = Livewire::actingAs($this->kyle)->test('pages::reviews.index')->set('show', 'partner');

        $this->assertEqualsCanonicalizing(['Both', 'Harry only', 'Harry dismissed'], $this->titles($component));
    }

    public function test_waiting_for_my_review_includes_dismissed_prompts(): void
    {
        $this->seedItems();

        $component = Livewire::actingAs($this->kyle)->test('pages::reviews.index')->set('show', 'waiting');

        $this->assertEqualsCanonicalizing(['Harry only', 'Harry dismissed'], $this->titles($component));
    }

    public function test_show_combines_with_category(): void
    {
        $this->seedItems();

        $component = Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->set('show', 'partner')
            ->set('category', 'book');

        $this->assertSame(['Harry only'], $this->titles($component));
    }

    public function test_show_options_are_from_each_members_point_of_view(): void
    {
        $this->seedItems();

        $component = Livewire::actingAs($this->harry)->test('pages::reviews.index')->set('show', 'mine');

        $this->assertEqualsCanonicalizing(['Both', 'Harry only', 'Harry dismissed'], $this->titles($component));
    }

    public function test_unknown_show_value_falls_back_to_all(): void
    {
        $this->seedItems();

        $component = Livewire::withQueryParams(['show' => 'nope'])
            ->actingAs($this->kyle)
            ->test('pages::reviews.index')
            ->assertOk();

        $this->assertCount(4, $this->titles($component));
    }

    public function test_show_select_names_the_partner(): void
    {
        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->assertSeeHtml('data-test="show-filter"')
            ->assertSee('Reviewed by Harry');
    }

    public function test_no_matches_message_when_filter_hides_everything(): void
    {
        $this->review($this->kyle, ReviewCategory::Film, 'Kyle only', '5');

        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->set('show', 'waiting')
            ->assertSeeHtml('data-test="reviews-no-matches"')
            ->assertDontSeeHtml('data-test="reviews-empty"');
    }

    public function test_all_controls_combine(): void
    {
        $this->review($this->harry, ReviewCategory::Restaurant, 'Dishoom', '4.5');
        $this->review($this->harry, ReviewCategory::Restaurant, 'Hoppers', '3');
        $this->review($this->kyle, ReviewCategory::Restaurant, 'Dishoom Express', '5');
        $this->review($this->harry, ReviewCategory::Film, 'Dishoom the Film', '5');

        $component = Livewire::withQueryParams(['category' => 'restaurant', 'show' => 'partner', 'q' => 'dish', 'sort' => 'partner'])
            ->actingAs($this->kyle)
            ->test('pages::reviews.index');

        $this->assertSame(['Dishoom'], $this->titles($component));

        $component->set('search', '');

        $this->assertSame(['Dishoom', 'Hoppers'], $this->titles($component));
    }

    public function test_unknown_values_fall_back_to_defaults(): void
    {
        $this->review($this->kyle, ReviewCategory::Film, 'Older', '5');
        $this->travel(1)->minutes();
        $this->review($this->kyle, ReviewCategory::Book, 'Newer', '1');

        $component = Livewire::withQueryParams(['show' => 'nope', 'sort' => 'nope', 'category' => 'nope'])
            ->actingAs($this->kyle)
            ->test('pages::reviews.index')
            ->assertOk();

        $this->assertSame(['Newer', 'Older'], $this->titles($component));
    }

    public function test_clear_filters_resets_category_show_and_search_but_keeps_sort(): void
    {
        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->set('category', 'film')
            ->set('show', 'waiting')
            ->set('search', 'dune')
            ->set('sort', 'partner')
            ->call('clearFilters')
            ->assertSet('category', null)
            ->assertSet('show', 'all')
            ->assertSet('search', '')
            ->assertSet('sort', 'partner');
    }

    public function test_clear_filters_button_only_shows_when_filtered(): void
    {
        $this->review($this->kyle, ReviewCategory::Film, 'Paddington 2', '4.5');

        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->assertDontSeeHtml('data-test="clear-filters-button"')
            ->set('sort', 'mine')
            ->assertDontSeeHtml('data-test="clear-filters-button"')
            ->set('search', 'x')
            ->assertSeeHtml('data-test="clear-filters-button"');
    }

    public function test_prompts_are_not_affected_by_filters(): void
    {
        $this->review($this->harry, ReviewCategory::Restaurant, 'Dishoom', '4.5');

        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->set('search', 'zzz')
            ->set('show', 'mine')
            ->assertSeeHtml('data-test="review-prompt"')
            ->assertSee('Dishoom');
    }

    public function test_page_still_requires_a_couple(): void
    {
        $single = User::factory()->create(['username' => 'single']);

        $this->actingAs($single)
            ->get(route('reviews.index', ['q' => 'x']))
            ->assertRedirect(route('partner.search'));
    }
}
