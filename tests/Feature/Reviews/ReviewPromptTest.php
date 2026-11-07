<?php

namespace Tests\Feature\Reviews;

use App\Actions\Reviews\CreateReview;
use App\Enums\ReviewCategory;
use App\Models\Couple;
use App\Models\Item;
use App\Models\Review;
use App\Models\ReviewPrompt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewPromptTest extends TestCase
{
    use RefreshDatabase;

    private User $kyle;

    private User $harry;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kyle = User::factory()->create(['name' => 'Kyle', 'username' => 'kyle']);
        $this->harry = User::factory()->create(['name' => 'Harry', 'username' => 'harry']);

        Couple::factory()->withMembers($this->kyle, $this->harry)->create();
        $this->kyle->refresh();
        $this->harry->refresh();

        [$review] = app(CreateReview::class)($this->kyle, null, ReviewCategory::Film, 'Paddington 2', '4.5', 'Kyle loved it.');
        $this->item = $review->item;
    }

    private function showAs(User $user): Testable
    {
        return Livewire::actingAs($user)->test('pages::reviews.show', ['item' => $this->item->id]);
    }

    private function harrysPrompt(): ?ReviewPrompt
    {
        return ReviewPrompt::where('user_id', $this->harry->id)->where('item_id', $this->item->id)->first();
    }

    public function test_first_review_prompts_partner_only(): void
    {
        $this->assertSame(1, $this->harry->reviewPrompts()->open()->count());
        $this->assertNotNull($this->harrysPrompt());
        $this->assertSame(0, $this->kyle->reviewPrompts()->count());
    }

    public function test_prompt_is_listed_on_index_with_partner_name(): void
    {
        $this->actingAs($this->harry)
            ->get(route('reviews.index'))
            ->assertOk()
            ->assertSeeHtml('data-test="review-prompt"')
            ->assertSeeInOrder(['Kyle', 'Paddington 2', 'Film'])
            ->assertSeeHtml('href="'.route('reviews.show', $this->item).'"');

        $this->actingAs($this->kyle)
            ->get(route('reviews.index'))
            ->assertDontSeeHtml('data-test="review-prompt"');
    }

    /**
     * Flux renders the Reviews nav badge (the only nav badge) with this marker.
     */
    private const NAV_BADGE = 'data-flux-navlist-badge>';

    public function test_nav_badge_shows_open_prompt_count(): void
    {
        $this->actingAs($this->harry)
            ->get(route('reviews.index'))
            ->assertSeeHtml(self::NAV_BADGE);

        $this->assertSame(1, $this->harry->openReviewPromptCount());

        $this->actingAs($this->kyle)
            ->get(route('reviews.index'))
            ->assertDontSeeHtml(self::NAV_BADGE);
    }

    public function test_answering_prompt_from_item_page_removes_it(): void
    {
        $this->showAs($this->harry)
            ->assertSeeHtml('data-test="add-review-form"')
            ->set('form.stars', '4')
            ->set('form.body', 'Harry liked it too.')
            ->call('addReview')
            ->assertHasNoErrors()
            ->assertSee('Harry liked it too.')
            ->assertSee('Kyle loved it.')
            ->assertDontSeeHtml('data-test="add-review-form"');

        $this->assertSame(2, $this->item->reviews()->count());
        $this->assertSame(8, $this->item->reviewBy($this->harry)?->rating);
        $this->assertNull($this->harrysPrompt());
        $this->assertSame(0, $this->harry->openReviewPromptCount());
    }

    public function test_add_form_shows_fixed_title_and_category(): void
    {
        $this->showAs($this->harry)
            ->assertSee('Paddington 2')
            ->assertSee('Film')
            ->assertDontSeeHtml('wire:model="form.title"')
            ->assertDontSeeHtml('wire:model="form.category"');
    }

    public function test_partner_review_is_visible_before_writing_own(): void
    {
        $this->showAs($this->harry)
            ->assertSeeHtml('data-test="partner-review"')
            ->assertSee('Kyle loved it.')
            ->assertSeeHtml('aria-label="4.5 out of 5 stars"');
    }

    public function test_dismissed_prompt_is_not_shown_again_but_item_can_still_be_reviewed(): void
    {
        Livewire::actingAs($this->harry)
            ->test('pages::reviews.index')
            ->call('dismissPrompt', $this->harrysPrompt()->id)
            ->assertDontSeeHtml('data-test="review-prompt"');

        $this->assertNotNull($this->harrysPrompt()?->dismissed_at);
        $this->assertSame(0, $this->harry->openReviewPromptCount());

        $this->actingAs($this->harry)
            ->get(route('reviews.index'))
            ->assertDontSeeHtml('data-test="review-prompt"')
            ->assertDontSeeHtml(self::NAV_BADGE);

        $this->showAs($this->harry)
            ->set('form.stars', '3')
            ->set('form.body', 'Fine.')
            ->call('addReview')
            ->assertHasNoErrors();

        $this->assertNotNull($this->item->reviewBy($this->harry));
    }

    public function test_attaching_via_create_page_clears_own_prompt(): void
    {
        Livewire::actingAs($this->harry)
            ->test('pages::reviews.create')
            ->set('form.category', 'film')
            ->set('form.title', 'paddington 2')
            ->set('form.stars', '5')
            ->set('form.body', 'Great.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->harrysPrompt());
        $this->assertSame(0, $this->kyle->reviewPrompts()->count());
    }

    public function test_reviewing_an_existing_item_does_not_prompt_anyone(): void
    {
        $this->showAs($this->harry)
            ->set('form.stars', '4')
            ->set('form.body', 'Nice.')
            ->call('addReview');

        $this->assertSame(0, ReviewPrompt::count());
    }

    public function test_add_review_on_item_page_validates_stars_and_body(): void
    {
        $this->showAs($this->harry)
            ->set('form.stars', '3.3')
            ->set('form.body', '  ')
            ->call('addReview')
            ->assertHasErrors(['form.stars' => 'in', 'form.body' => 'required']);

        $this->assertNull($this->item->reviewBy($this->harry));
        $this->assertNotNull($this->harrysPrompt());
    }

    public function test_answering_after_item_deleted_shows_message(): void
    {
        $page = $this->showAs($this->harry)
            ->set('form.stars', '4')
            ->set('form.body', 'Too late.');

        $this->item->delete();

        $page->call('addReview')
            ->assertRedirect(route('reviews.index'));

        $this->assertSame(0, Review::count());
    }
}
