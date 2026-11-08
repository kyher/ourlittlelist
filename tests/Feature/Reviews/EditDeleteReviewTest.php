<?php

namespace Tests\Feature\Reviews;

use App\Actions\Reviews\CreateReview;
use App\Actions\Reviews\DeleteReview;
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

class EditDeleteReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $kyle;

    private User $harry;

    private Item $item;

    private Review $kylesReview;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kyle = User::factory()->create(['name' => 'Kyle', 'username' => 'kyle']);
        $this->harry = User::factory()->create(['name' => 'Harry', 'username' => 'harry']);

        Couple::factory()->withMembers($this->kyle, $this->harry)->create();
        $this->kyle->refresh();
        $this->harry->refresh();

        [$this->kylesReview] = $this->review($this->kyle, 'Paddington 2', '4.5', 'Lovely.');
        $this->item = $this->kylesReview->item;
    }

    /**
     * @return array{0: Review, 1: bool}
     */
    private function review(User $user, string $title, string $stars, string $body, ReviewCategory $category = ReviewCategory::Film): array
    {
        return app(CreateReview::class)($user, null, $category, $title, $stars, $body);
    }

    private function showAs(User $user, ?Item $item = null): Testable
    {
        return Livewire::actingAs($user)->test('pages::reviews.show', ['item' => ($item ?? $this->item)->id]);
    }

    public function test_author_can_edit_stars_and_body(): void
    {
        $this->showAs($this->kyle)
            ->assertDontSeeHtml('data-test="review-edited"')
            ->call('startEditing')
            ->assertSet('form.stars', '4.5')
            ->assertSet('form.body', 'Lovely.')
            ->set('form.stars', '5')
            ->set('form.body', 'Even better the second time.')
            ->call('updateReview')
            ->assertHasNoErrors()
            ->assertSet('editing', false)
            ->assertSee('Even better the second time.')
            ->assertSeeHtml('data-test="review-edited"');

        $review = $this->kylesReview->refresh();
        $this->assertSame(10, $review->rating);
        $this->assertSame('Even better the second time.', $review->body);
        $this->assertNotNull($review->edited_at);
    }

    public function test_editing_whole_star_review_prefills_whole_number(): void
    {
        $this->kylesReview->forceFill(['rating' => 8])->save();

        $this->showAs($this->kyle)
            ->call('startEditing')
            ->assertSet('form.stars', '4');
    }

    public function test_edit_requires_stars_and_body(): void
    {
        $this->showAs($this->kyle)
            ->call('startEditing')
            ->set('form.stars', null)
            ->set('form.body', '   ')
            ->call('updateReview')
            ->assertHasErrors(['form.stars' => 'required', 'form.body' => 'required'])
            ->assertSet('editing', true);

        $this->assertSame(9, $this->kylesReview->refresh()->rating);
        $this->assertNull($this->kylesReview->edited_at);
    }

    public function test_edit_does_not_create_a_prompt(): void
    {
        ReviewPrompt::where('user_id', $this->harry->id)->update(['dismissed_at' => now()]);

        $this->showAs($this->kyle)
            ->call('startEditing')
            ->set('form.body', 'Changed my mind.')
            ->call('updateReview');

        $this->assertSame(0, $this->harry->openReviewPromptCount());
        $this->assertSame(1, ReviewPrompt::count());
    }

    public function test_cancel_editing_discards_changes(): void
    {
        $this->showAs($this->kyle)
            ->call('startEditing')
            ->set('form.body', 'Not saved.')
            ->call('cancelEditing')
            ->assertSet('editing', false)
            ->assertSee('Lovely.');

        $this->assertSame('Lovely.', $this->kylesReview->refresh()->body);
    }

    public function test_removing_review_keeps_item_when_partner_has_reviewed(): void
    {
        $this->review($this->harry, 'Paddington 2', '4', 'Good.');

        $this->showAs($this->kyle)
            ->assertSeeHtml('data-test="confirm-delete-review-button"')
            ->call('deleteReview')
            ->assertNoRedirect()
            ->assertSeeHtml('data-test="add-review-form"')
            ->assertSee('Good.');

        $this->assertModelMissing($this->kylesReview);
        $this->assertModelExists($this->item);
        $this->assertSame(1, $this->item->reviews()->count());
        $this->assertSame(0, $this->kyle->reviewPrompts()->count());
    }

    public function test_removing_last_review_deletes_item_and_prompts(): void
    {
        $prompt = ReviewPrompt::where('user_id', $this->harry->id)->firstOrFail();

        $this->showAs($this->kyle)
            ->call('deleteReview')
            ->assertRedirect(route('reviews.index'));

        $this->assertModelMissing($this->kylesReview);
        $this->assertModelMissing($this->item);
        $this->assertModelMissing($prompt);
        $this->assertSame(0, $this->harry->openReviewPromptCount());
    }

    public function test_delete_action_reports_whether_item_was_removed(): void
    {
        [$harrys] = $this->review($this->harry, 'Paddington 2', '4', 'Good.');

        $this->assertFalse(app(DeleteReview::class)($this->kylesReview));
        $this->assertTrue(app(DeleteReview::class)($harrys));
        $this->assertSame(0, Item::count());
    }

    public function test_readding_after_removal_does_not_prompt_again_when_partner_reviewed(): void
    {
        $this->review($this->harry, 'Paddington 2', '4', 'Good.');
        app(DeleteReview::class)($this->kylesReview);

        $this->review($this->kyle, 'Paddington 2', '3', 'Second thoughts.');

        $this->assertSame(0, ReviewPrompt::count());
    }

    public function test_partner_sees_no_edit_or_remove_controls_for_others_review(): void
    {
        $this->showAs($this->harry)
            ->assertDontSeeHtml('data-test="edit-review-button"')
            ->assertDontSeeHtml('data-test="delete-review-button"');
    }

    public function test_user_without_a_review_cannot_edit_or_remove(): void
    {
        $this->showAs($this->harry)->call('updateReview')->assertNotFound();
        $this->showAs($this->harry)->call('deleteReview')->assertNotFound();

        $this->assertModelExists($this->kylesReview);
    }
}
