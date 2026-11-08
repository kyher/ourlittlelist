<?php

namespace Tests\Feature\Admin;

use App\Actions\Couples\EndCouple;
use App\Actions\Reviews\CreateReview;
use App\Enums\ReviewCategory;
use App\Models\Admin;
use App\Models\Couple;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: Couple}
     */
    private function couple(array $firstAttributes = [], array $secondAttributes = []): array
    {
        $first = User::factory()->create($firstAttributes);
        $second = User::factory()->create($secondAttributes);
        $couple = Couple::factory()->withMembers($first, $second)->create();

        return [$first->refresh(), $second->refresh(), $couple];
    }

    private function review(User $user, string $title, string $body = 'Some thoughts.'): Review
    {
        return app(CreateReview::class)($user, null, ReviewCategory::Film, $title, '4', $body)[0];
    }

    private function viewDashboard(): TestResponse
    {
        return $this->actingAs(Admin::factory()->create(), 'admin')->get(route('admin.dashboard'));
    }

    public function test_admins_are_not_asked_to_verify_an_email(): void
    {
        User::factory()->unverified()->create();

        $this->viewDashboard()->assertOk();
    }

    public function test_the_totals_match_the_data(): void
    {
        [$first, $second] = $this->couple();
        $this->couple();
        User::factory()->create();

        $this->review($first, 'One');
        $this->review($second, 'Two');
        $this->review($first, 'Three');

        $this->viewDashboard()
            ->assertOk()
            ->assertViewHas('totals', ['users' => 5, 'couples' => 2, 'reviews' => 3])
            ->assertSeeInOrder([__('Users'), '5', __('Couples'), '2', __('Reviews'), '3']);
    }

    public function test_each_partners_review_of_the_same_item_counts_separately(): void
    {
        [$first, $second] = $this->couple();

        $this->review($first, 'Shared Film');
        $this->review($second, 'Shared Film');

        $this->assertSame(1, Item::count());

        $this->viewDashboard()->assertViewHas('totals.reviews', 2);
    }

    public function test_admins_are_not_counted_as_users(): void
    {
        Admin::factory()->count(3)->create();
        User::factory()->create();

        $this->viewDashboard()->assertViewHas('totals.users', 1);
    }

    public function test_an_empty_system_shows_zero_for_every_total(): void
    {
        $this->viewDashboard()
            ->assertViewHas('totals', ['users' => 0, 'couples' => 0, 'reviews' => 0])
            ->assertSeeInOrder([__('Users'), '0', __('Couples'), '0', __('Reviews'), '0']);
    }

    public function test_the_totals_stay_current_when_a_couple_ends(): void
    {
        [, , $couple] = $this->couple();

        $this->viewDashboard()->assertViewHas('totals.couples', 1);

        app(EndCouple::class)($couple);

        $this->get(route('admin.dashboard'))->assertViewHas('totals.couples', 0);
    }

    public function test_no_personal_data_or_review_content_is_shown(): void
    {
        [$first, $second] = $this->couple(
            ['name' => 'Firstname Surname', 'username' => 'firstusername', 'email' => 'first@example.com'],
            ['name' => 'Othername Lastname', 'username' => 'otherusername', 'email' => 'other@example.com'],
        );
        $this->review($first, 'Very Private Title', 'A very private opinion.');

        $admin = Admin::factory()->create(['email' => 'operator@example.com']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Firstname Surname')
            ->assertDontSee('Othername Lastname')
            ->assertDontSee($first->username)
            ->assertDontSee($second->username)
            ->assertDontSee($first->email)
            ->assertDontSee($second->email)
            ->assertDontSee('operator@example.com')
            ->assertDontSee('Very Private Title')
            ->assertDontSee('A very private opinion.');
    }

    public function test_a_guest_cannot_see_the_totals(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'))
            ->assertDontSee(__('Reviews'));
    }

    public function test_a_regular_user_cannot_see_the_totals(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'))
            ->assertDontSee(__('Reviews'));
    }
}
