<?php

namespace Tests\Feature\Reviews;

use App\Actions\Couples\EndCouple;
use App\Actions\Reviews\CreateReview;
use App\Enums\ReviewCategory;
use App\Exceptions\ReviewActionException;
use App\Models\Couple;
use App\Models\Item;
use App\Models\Review;
use App\Models\ReviewPrompt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $kyle;

    private User $harry;

    private User $sam;

    private User $alex;

    private Couple $couple;

    private Couple $otherCouple;

    private Review $kylesReview;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kyle = User::factory()->create(['name' => 'Kyle', 'username' => 'kyle']);
        $this->harry = User::factory()->create(['name' => 'Harry', 'username' => 'harry']);
        $this->sam = User::factory()->create(['name' => 'Sam', 'username' => 'sam']);
        $this->alex = User::factory()->create(['name' => 'Alex', 'username' => 'alex']);

        $this->couple = Couple::factory()->withMembers($this->kyle, $this->harry)->create();
        $this->otherCouple = Couple::factory()->withMembers($this->sam, $this->alex)->create();

        foreach ([$this->kyle, $this->harry, $this->sam, $this->alex] as $user) {
            $user->refresh();
        }

        [$this->kylesReview] = app(CreateReview::class)($this->kyle, null, ReviewCategory::Film, 'Paddington 2', '4.5', 'Secret thoughts.');
        $this->item = $this->kylesReview->item;
    }

    public function test_other_couples_item_page_is_404(): void
    {
        $this->actingAs($this->sam)
            ->get(route('reviews.show', $this->item))
            ->assertNotFound()
            ->assertDontSee('Paddington 2');

        $this->actingAs($this->sam)
            ->get(route('reviews.show', 999999))
            ->assertNotFound();
    }

    public function test_other_couple_cannot_review_the_item(): void
    {
        $this->assertTrue(Gate::forUser($this->harry)->allows('view', $this->item));
        $this->assertFalse(Gate::forUser($this->sam)->allows('view', $this->item));
        $this->assertFalse(Gate::forUser($this->sam)->allows('review', $this->item));

        $this->expectException(ReviewActionException::class);
        $this->expectExceptionMessage('This item no longer exists.');

        app(CreateReview::class)($this->sam, $this->item, null, null, '1', 'Sneaky.');
    }

    public function test_partner_cannot_update_or_delete_others_review(): void
    {
        $this->assertTrue(Gate::forUser($this->kyle)->allows('update', $this->kylesReview));
        $this->assertTrue(Gate::forUser($this->kyle)->allows('delete', $this->kylesReview));
        $this->assertTrue(Gate::forUser($this->harry)->denies('update', $this->kylesReview));
        $this->assertTrue(Gate::forUser($this->harry)->denies('delete', $this->kylesReview));
        $this->assertTrue(Gate::forUser($this->sam)->denies('update', $this->kylesReview));

        app(CreateReview::class)($this->harry, $this->item, null, null, '2', 'Harry.');

        Livewire::actingAs($this->harry)
            ->test('pages::reviews.show', ['item' => $this->item->id])
            ->call('startEditing')
            ->set('form.stars', '1')
            ->set('form.body', 'Changed by Harry.')
            ->call('updateReview')
            ->call('deleteReview');

        $this->kylesReview->refresh();
        $this->assertSame(9, $this->kylesReview->rating);
        $this->assertSame('Secret thoughts.', $this->kylesReview->body);
        $this->assertNull($this->item->reviewBy($this->harry));
    }

    public function test_author_who_left_the_couple_cannot_update_their_old_review(): void
    {
        $this->kyle->forceFill(['couple_id' => null])->save();

        $this->assertTrue(Gate::forUser($this->kyle->refresh())->denies('update', $this->kylesReview));
    }

    public function test_prompt_cannot_be_dismissed_by_someone_else(): void
    {
        $prompt = ReviewPrompt::where('user_id', $this->harry->id)->firstOrFail();

        $this->assertTrue(Gate::forUser($this->kyle)->denies('dismiss', $prompt));

        Livewire::actingAs($this->kyle)
            ->test('pages::reviews.index')
            ->call('dismissPrompt', $prompt->id)
            ->assertNotFound();

        Livewire::actingAs($this->sam)
            ->test('pages::reviews.index')
            ->call('dismissPrompt', $prompt->id)
            ->assertNotFound();

        $this->assertNull($prompt->refresh()->dismissed_at);
    }

    public function test_other_couples_items_never_listed(): void
    {
        $this->actingAs($this->sam)
            ->get(route('reviews.index'))
            ->assertOk()
            ->assertDontSee('Paddington 2')
            ->assertDontSee('Secret thoughts.')
            ->assertDontSeeHtml('data-test="review-prompt"');
    }

    public function test_same_title_in_another_couple_is_a_separate_item(): void
    {
        app(CreateReview::class)($this->sam, null, ReviewCategory::Film, 'Paddington 2', '2', 'Ours.');

        $this->assertSame(2, Item::count());
        $this->assertSame(1, $this->item->reviews()->count());
    }

    public function test_ending_couple_deletes_all_items_reviews_and_prompts(): void
    {
        app(CreateReview::class)($this->harry, null, ReviewCategory::Book, 'Dune', '3', 'Long.');
        app(CreateReview::class)($this->sam, null, ReviewCategory::Food, 'Ramen', '5', 'Others.');

        app(EndCouple::class)($this->couple);

        $this->assertSame(0, Item::where('couple_id', $this->couple->id)->count());
        $this->assertSame(0, Review::whereIn('user_id', [$this->kyle->id, $this->harry->id])->count());
        $this->assertSame(0, ReviewPrompt::whereIn('user_id', [$this->kyle->id, $this->harry->id])->count());

        $this->assertSame(1, Item::where('couple_id', $this->otherCouple->id)->count());
        $this->assertSame(1, Review::where('user_id', $this->sam->id)->count());
        $this->assertSame(1, ReviewPrompt::where('user_id', $this->alex->id)->count());
    }

    public function test_action_fails_when_couple_ended_mid_form(): void
    {
        $page = Livewire::actingAs($this->kyle)
            ->test('pages::reviews.create')
            ->set('form.category', 'book')
            ->set('form.title', 'Dune')
            ->set('form.stars', '3')
            ->set('form.body', 'Long.');

        $this->couple->delete();

        $page->call('save')
            ->assertHasErrors('form')
            ->assertSee('no longer in a couple')
            ->assertNoRedirect();

        $this->assertSame(0, Item::where('title', 'Dune')->count());

        $this->expectException(ReviewActionException::class);
        $this->expectExceptionMessage("You're no longer in a couple.");

        app(CreateReview::class)($this->kyle->refresh(), null, ReviewCategory::Book, 'Dune', '3', 'Long.');
    }
}
