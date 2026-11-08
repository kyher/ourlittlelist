<?php

namespace Tests\Feature\Reviews;

use App\Enums\ReviewCategory;
use App\Models\Couple;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $kyle;

    private User $harry;

    private User $sam;

    private Couple $couple;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kyle = User::factory()->create(['name' => 'Kyle', 'username' => 'kyle']);
        $this->harry = User::factory()->create(['name' => 'Harry', 'username' => 'harry']);
        $this->sam = User::factory()->create(['name' => 'Sam', 'username' => 'sam']);

        $this->couple = Couple::factory()->withMembers($this->kyle, $this->harry)->create();
        $this->kyle->refresh();
        $this->harry->refresh();
    }

    /**
     * @param  array<string, string|null>  $fields
     */
    private function createAs(User $user, array $fields = []): Testable
    {
        $fields = array_merge([
            'category' => 'film',
            'title' => 'Paddington 2',
            'stars' => '4.5',
            'body' => 'Warm, funny and kind.',
        ], $fields);

        $test = Livewire::actingAs($user)->test('pages::reviews.create');

        foreach ($fields as $field => $value) {
            $test->set('form.'.$field, $value);
        }

        return $test->call('save');
    }

    public function test_member_can_review_a_new_item(): void
    {
        $this->createAs($this->kyle)
            ->assertHasNoErrors()
            ->assertRedirect(route('reviews.show', Item::firstOrFail()));

        $item = Item::firstOrFail();
        $this->assertSame($this->couple->id, $item->couple_id);
        $this->assertSame(ReviewCategory::Film, $item->category);
        $this->assertSame('Paddington 2', $item->title);
        $this->assertSame('paddington 2', $item->title_key);

        $review = Review::firstOrFail();
        $this->assertSame($this->kyle->id, $review->user_id);
        $this->assertSame($item->id, $review->item_id);
        $this->assertSame(9, $review->rating);
        $this->assertSame(4.5, $review->stars());
        $this->assertSame('Warm, funny and kind.', $review->body);
    }

    public function test_new_review_appears_in_the_couples_list(): void
    {
        $this->createAs($this->kyle);

        foreach ([$this->kyle, $this->harry] as $member) {
            $this->actingAs($member)
                ->get(route('reviews.index'))
                ->assertOk()
                ->assertSeeHtml('data-test="review-item"')
                ->assertSee('Paddington 2');
        }
    }

    public function test_rating_and_body_are_required(): void
    {
        $this->createAs($this->kyle, ['stars' => null, 'body' => '   '])
            ->assertHasErrors(['form.stars' => 'required', 'form.body' => 'required'])
            ->assertSee('Choose a rating from 0.5 to 5 stars.')
            ->assertSee('Write your review.');

        $this->assertSame(0, Item::count());
        $this->assertSame(0, Review::count());
    }

    public function test_title_and_category_are_required(): void
    {
        $this->createAs($this->kyle, ['category' => null, 'title' => '  '])
            ->assertHasErrors(['form.category' => 'required', 'form.title' => 'required']);

        $this->assertSame(0, Item::count());
    }

    public function test_category_starts_on_the_placeholder(): void
    {
        // With null, no option matched, so the browser showed "Film" as chosen while the value
        // stayed empty, and picking Film sent no change ("Category is required").
        Livewire::actingAs($this->kyle)->test('pages::reviews.create')
            ->assertSet('form.category', '')
            ->assertSeeHtml('<option value="" disabled selected class="placeholder">')
            ->call('save')
            ->assertHasErrors(['form.category' => 'required']);
    }

    public function test_unknown_category_is_refused(): void
    {
        $this->createAs($this->kyle, ['category' => 'podcast'])
            ->assertHasErrors(['form.category']);

        $this->assertSame(0, Item::count());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidStars(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-0.5'],
            'quarter' => ['0.25'],
            'not a half step' => ['3.3'],
            'above five' => ['6'],
            'five and a half' => ['5.5'],
            'not a number' => ['abc'],
        ];
    }

    #[DataProvider('invalidStars')]
    public function test_only_half_star_steps_are_accepted(string $stars): void
    {
        $this->createAs($this->kyle, ['stars' => $stars])
            ->assertHasErrors(['form.stars']);

        $this->assertSame(0, Review::count());
    }

    public function test_every_half_star_step_is_accepted(): void
    {
        foreach (Review::STAR_VALUES as $i => $stars) {
            $this->createAs($this->kyle, ['title' => 'Film '.$i, 'stars' => $stars])
                ->assertHasNoErrors();
        }

        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Review::orderBy('rating')->pluck('rating')->all());
    }

    public function test_body_over_5000_characters_is_refused(): void
    {
        $this->createAs($this->kyle, ['body' => str_repeat('a', 5001)])
            ->assertHasErrors(['form.body' => 'max']);

        $this->createAs($this->kyle, ['body' => str_repeat('a', 5000)])
            ->assertHasNoErrors();
    }

    public function test_title_over_255_characters_is_refused(): void
    {
        $this->createAs($this->kyle, ['title' => str_repeat('a', 256)])
            ->assertHasErrors(['form.title' => 'max']);

        $this->assertSame(0, Item::count());
    }

    public function test_same_category_and_title_ignoring_case_and_spaces_attaches_to_existing_item(): void
    {
        $this->createAs($this->kyle);

        $this->createAs($this->harry, ['title' => '  PADDINGTON 2 ', 'stars' => '4'])
            ->assertHasNoErrors()
            ->assertRedirect(route('reviews.show', Item::firstOrFail()));

        $this->assertSame(1, Item::count());
        $this->assertSame('Paddington 2', Item::firstOrFail()->title);
        $this->assertSame(2, Item::firstOrFail()->reviews()->count());
    }

    public function test_same_title_in_another_category_is_a_separate_item(): void
    {
        $this->createAs($this->kyle, ['title' => 'Dune']);
        $this->createAs($this->kyle, ['title' => 'Dune', 'category' => 'book'])
            ->assertHasNoErrors();

        $this->assertSame(2, Item::count());
    }

    public function test_second_review_of_same_item_by_same_user_is_refused(): void
    {
        $this->createAs($this->kyle);

        $this->createAs($this->kyle, ['title' => 'paddington 2'])
            ->assertHasErrors('form')
            ->assertSee("You've already reviewed Paddington 2. Edit your review instead.")
            ->assertSeeHtml('href="'.route('reviews.show', Item::firstOrFail()).'"')
            ->assertNoRedirect();

        $this->assertSame(1, Review::count());
    }

    public function test_review_text_is_escaped(): void
    {
        $this->createAs($this->kyle, ['body' => '<script>alert(1)</script>']);

        $this->actingAs($this->kyle)
            ->get(route('reviews.show', Item::firstOrFail()))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_single_user_is_redirected_from_review_pages(): void
    {
        foreach (['reviews.index', 'reviews.create'] as $route) {
            $this->actingAs($this->sam)
                ->get(route($route))
                ->assertRedirect(route('partner.search'))
                ->assertSessionHas('status', 'Connect with your partner to start reviewing.');
        }

        Livewire::actingAs($this->sam)->test('pages::partner.search')
            ->assertOk();
    }
}
