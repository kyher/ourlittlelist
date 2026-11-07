<?php

namespace Tests\Feature\Couples;

use App\Actions\Couples\EndCouple;
use App\Actions\Couples\SendCoupleRequest;
use App\Models\Couple;
use App\Models\CoupleRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class EndCoupleTest extends TestCase
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

        foreach ([$this->kyle, $this->harry] as $user) {
            RateLimiter::clear('couple-request:'.$user->id);
        }

        $this->couple = Couple::factory()->withMembers($this->kyle, $this->harry)->create();
        $this->kyle->refresh();
        $this->harry->refresh();
    }

    private function assertBothSingle(): void
    {
        $this->assertSame(0, Couple::count());
        $this->assertNull($this->kyle->refresh()->couple_id);
        $this->assertNull($this->harry->refresh()->couple_id);
    }

    public function test_ending_a_couple_deletes_it_and_frees_both_members(): void
    {
        app(EndCouple::class)($this->couple);

        $this->assertBothSingle();
    }

    public function test_either_member_can_end_the_couple_from_the_couple_page(): void
    {
        foreach ([$this->kyle, $this->harry] as $member) {
            $couple = Couple::count() ? $this->couple : Couple::factory()->withMembers($this->kyle, $this->harry)->create();

            Livewire::actingAs($member->refresh())
                ->test('pages::couple.show')
                ->call('endCouple')
                ->assertRedirect(route('partner.search'));

            $this->assertModelMissing($couple);
            $this->assertBothSingle();
        }
    }

    public function test_only_members_can_end_a_couple(): void
    {
        $this->assertTrue(Gate::forUser($this->kyle)->allows('end', $this->couple));
        $this->assertTrue(Gate::forUser($this->harry)->allows('end', $this->couple));
        $this->assertFalse(Gate::forUser($this->sam)->allows('end', $this->couple));

        $other = Couple::factory()->withMembers($this->sam, User::factory()->create())->create();
        $this->assertFalse(Gate::forUser($this->kyle)->allows('end', $other));
    }

    public function test_deleting_a_members_account_ends_the_couple(): void
    {
        $pending = CoupleRequest::factory()->create(['sender_id' => $this->sam->id, 'receiver_id' => $this->kyle->id]);

        $this->kyle->delete();

        $this->assertSame(0, Couple::count());
        $this->assertNull($this->harry->refresh()->couple_id);
        $this->assertModelMissing($pending);
    }

    public function test_both_members_can_send_requests_again_after_ending(): void
    {
        app(EndCouple::class)($this->couple);

        app(SendCoupleRequest::class)($this->kyle->refresh(), $this->sam);
        app(SendCoupleRequest::class)($this->harry->refresh(), $this->kyle);

        $this->assertSame(2, CoupleRequest::query()->pending()->count());
    }
}
