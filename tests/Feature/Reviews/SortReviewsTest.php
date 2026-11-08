<?php

namespace Tests\Feature\Reviews;

use App\Actions\Reviews\CreateReview;
use App\Enums\ReviewCategory;
use App\Models\Couple;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class SortReviewsTest extends TestCase
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

        // Most recent activity order: D, C, B, A.
        $this->item('A', kyle: '5', harry: '2');
        $this->item('B', kyle: '3.5');
        $this->item('C', harry: '4.5');
        $this->item('D', kyle: '4', harry: '3');
    }

    /**
     * Create an item a minute after the previous one, reviewed by whoever has stars.
     */
    private function item(string $title, ?string $kyle = null, ?string $harry = null): Item
    {
        $this->travel(1)->minutes();

        $item = null;

        foreach ([[$this->kyle, $kyle], [$this->harry, $harry]] as [$user, $stars]) {
            if ($stars === null) {
                continue;
            }

            $item = app(CreateReview::class)($user, $item, $item ? null : ReviewCategory::Film, $item ? null : $title, $stars, 'Some thoughts.')[0]->item;
        }

        return $item;
    }

    /**
     * @return list<string>
     */
    private function titles(Testable $component): array
    {
        return array_column($component->instance()->items, 'title');
    }

    /**
     * @return list<string>
     */
    private function sorted(User $user, string $sort): array
    {
        return $this->titles(Livewire::actingAs($user)->test('pages::reviews.index')->set('sort', $sort));
    }

    public function test_default_order_is_most_recent(): void
    {
        $this->assertSame(['D', 'C', 'B', 'A'], $this->titles(Livewire::actingAs($this->kyle)->test('pages::reviews.index')));
    }

    public function test_my_highest_rated(): void
    {
        $this->assertSame(['A', 'D', 'B', 'C'], $this->sorted($this->kyle, 'mine'));
    }

    public function test_partners_highest_rated(): void
    {
        $this->assertSame(['C', 'D', 'A', 'B'], $this->sorted($this->kyle, 'partner'));
    }

    public function test_ties_are_ordered_by_most_recent_activity(): void
    {
        $this->item('E', kyle: '5');

        $this->assertSame(['E', 'A', 'D', 'B', 'C'], $this->sorted($this->kyle, 'mine'));
    }

    public function test_unrated_items_keep_most_recent_order(): void
    {
        $this->item('F', harry: '1');

        $this->assertSame(['A', 'D', 'B', 'F', 'C'], $this->sorted($this->kyle, 'mine'));
    }

    public function test_sort_is_from_each_members_point_of_view(): void
    {
        $this->assertSame(['C', 'D', 'A', 'B'], $this->sorted($this->harry, 'mine'));
    }

    public function test_unknown_sort_falls_back_to_most_recent(): void
    {
        $component = Livewire::withQueryParams(['sort' => 'nope'])
            ->actingAs($this->kyle)
            ->test('pages::reviews.index');

        $this->assertSame(['D', 'C', 'B', 'A'], $this->titles($component));
    }

    public function test_sort_select_names_the_partner(): void
    {
        Livewire::actingAs($this->kyle)->test('pages::reviews.index')
            ->assertSeeHtml('data-test="sort-select"')
            ->assertSee("Harry's highest rated");
    }
}
