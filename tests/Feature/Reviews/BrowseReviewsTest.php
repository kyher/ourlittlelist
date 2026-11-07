<?php

namespace Tests\Feature\Reviews;

use App\Actions\Reviews\CreateReview;
use App\Actions\Reviews\UpdateReview;
use App\Enums\ReviewCategory;
use App\Models\Couple;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BrowseReviewsTest extends TestCase
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

    private function review(User $user, ReviewCategory $category, string $title, string $stars, string $body = 'Some thoughts.'): Review
    {
        return app(CreateReview::class)($user, null, $category, $title, $stars, $body)[0];
    }

    public function test_items_are_ordered_by_most_recent_activity(): void
    {
        $oldest = $this->review($this->kyle, ReviewCategory::Film, 'Oldest', '3');
        $this->travel(1)->minutes();
        $this->review($this->kyle, ReviewCategory::Book, 'Middle', '3');
        $this->travel(1)->minutes();
        $this->review($this->kyle, ReviewCategory::Food, 'Newest', '3');

        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->assertSeeInOrder(['Newest', 'Middle', 'Oldest']);

        $this->travel(1)->minutes();
        app(UpdateReview::class)($oldest, '4', 'Revisited.');

        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->assertSeeInOrder(['Oldest', 'Newest', 'Middle']);
    }

    public function test_index_shows_both_partners_ratings_or_not_reviewed(): void
    {
        $this->review($this->kyle, ReviewCategory::Film, 'Paddington 2', '4.5');
        app(CreateReview::class)($this->harry, $this->review($this->kyle, ReviewCategory::Book, 'Dune', '3')->item, null, null, '2', 'Slow.');

        Livewire::actingAs($this->harry)->test('pages::reviews.index')
            ->assertSeeInOrder(['Dune', 'You', '2 out of 5 stars', 'Kyle', '3 out of 5 stars'])
            ->assertSeeInOrder(['Paddington 2', 'You', 'Not reviewed', 'Kyle', '4.5 out of 5 stars']);
    }

    public function test_category_filter_shows_only_that_category(): void
    {
        $this->review($this->kyle, ReviewCategory::Restaurant, 'Dishoom', '5');
        $this->review($this->kyle, ReviewCategory::Film, 'Paddington 2', '4.5');

        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->assertSeeHtml('data-test="category-filter"')
            ->set('category', 'restaurant')
            ->assertSee('Dishoom')
            ->assertDontSee('Paddington 2');

        Livewire::actingAs($this->kyle)
            ->withQueryParams(['category' => 'restaurant'])
            ->test('pages::reviews.index')
            ->assertSee('Dishoom')
            ->assertDontSee('Paddington 2');
    }

    public function test_invalid_category_filter_is_ignored(): void
    {
        $this->review($this->kyle, ReviewCategory::Restaurant, 'Dishoom', '5');
        $this->review($this->kyle, ReviewCategory::Film, 'Paddington 2', '4.5');

        $this->actingAs($this->kyle)
            ->get(route('reviews.index', ['category' => 'nope']))
            ->assertOk()
            ->assertSee('Dishoom')
            ->assertSee('Paddington 2');
    }

    public function test_filter_with_no_matches_says_so(): void
    {
        $this->review($this->kyle, ReviewCategory::Film, 'Paddington 2', '4.5');

        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->set('category', 'book')
            ->assertSeeHtml('data-test="reviews-no-matches"')
            ->assertSee('Nothing matches your search and filters.')
            ->assertDontSee('Paddington 2');
    }

    public function test_item_page_shows_both_reviews_side_by_side(): void
    {
        $kyles = $this->review($this->kyle, ReviewCategory::Film, 'Paddington 2', '4.5', 'Kyle loved it.');
        app(CreateReview::class)($this->harry, $kyles->item, null, null, '3', 'Harry was less sure.');

        $this->actingAs($this->kyle)
            ->get(route('reviews.show', $kyles->item))
            ->assertOk()
            ->assertSeeHtml('data-test="my-review"')
            ->assertSeeHtml('data-test="partner-review"')
            ->assertSeeInOrder(['You', '4.5 out of 5 stars', 'Kyle loved it.', 'Harry', '3 out of 5 stars', 'Harry was less sure.'])
            ->assertSee($kyles->created_at?->toFormattedDateString() ?? '');
    }
}
