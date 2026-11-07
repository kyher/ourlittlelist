<?php

namespace Tests\Feature\Couples;

use App\Models\Couple;
use App\Models\CoupleRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class CouplePagesTest extends TestCase
{
    use RefreshDatabase;

    private User $kyle;

    private User $harry;

    private User $sam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kyle = User::factory()->create(['name' => 'Kyle', 'username' => 'kyle', 'email' => 'kyle@example.com']);
        $this->harry = User::factory()->create(['name' => 'Harry', 'username' => 'harry', 'email' => 'harry@example.com']);
        $this->sam = User::factory()->create(['name' => 'Sam', 'username' => 'sam', 'email' => 'sam@example.com']);

        foreach ([$this->kyle, $this->harry, $this->sam] as $user) {
            RateLimiter::clear('partner-search:'.$user->id);
            RateLimiter::clear('couple-request:'.$user->id);
        }
    }

    private function searchAs(User $user, string $query)
    {
        return Livewire::actingAs($user)
            ->test('pages::partner.search')
            ->set('query', $query)
            ->call('search');
    }

    // US1 ---------------------------------------------------------------

    public function test_a_user_in_a_couple_is_redirected_from_search_to_their_reviews(): void
    {
        Couple::factory()->withMembers($this->kyle, $this->harry)->create();

        $this->actingAs($this->kyle->refresh())
            ->get(route('partner.search'))
            ->assertRedirect(route('reviews.index'));
    }

    public function test_a_single_search_result_offers_to_send_a_request(): void
    {
        $this->searchAs($this->kyle, 'harry')
            ->assertSeeHtml('data-test="send-request-button"')
            ->assertDontSeeHtml('data-test="partner-unavailable"');
    }

    public function test_a_user_in_a_couple_is_shown_as_not_available_without_revealing_their_partner(): void
    {
        Couple::factory()->withMembers($this->harry, $this->sam)->create();

        $this->searchAs($this->kyle, 'harry')
            ->assertSeeHtml('data-test="partner-unavailable"')
            ->assertSee('Not available')
            ->assertDontSeeHtml('data-test="send-request-button"')
            ->assertDontSee('Sam')
            ->assertDontSee('@sam');
    }

    public function test_sending_a_request_shows_it_as_pending(): void
    {
        $this->searchAs($this->kyle, 'harry')
            ->call('sendRequest')
            ->assertHasNoErrors()
            ->assertSeeHtml('data-test="outgoing-request"')
            ->assertSee('waiting for Harry')
            ->assertSee('Request pending');

        $this->assertDatabaseHas('couple_requests', [
            'sender_id' => $this->kyle->id,
            'receiver_id' => $this->harry->id,
            'status' => 'pending',
        ]);
    }

    public function test_a_refused_request_shows_the_reason(): void
    {
        CoupleRequest::factory()->create(['sender_id' => $this->kyle->id, 'receiver_id' => $this->sam->id]);

        $this->searchAs($this->kyle, 'harry')
            ->call('sendRequest')
            ->assertHasErrors('query')
            ->assertSee('You already have a pending request. Cancel it first.');

        $this->assertSame(1, CoupleRequest::count());
    }

    public function test_a_declined_request_is_shown_to_the_sender_and_can_be_dismissed(): void
    {
        CoupleRequest::factory()->declined()->create(['sender_id' => $this->kyle->id, 'receiver_id' => $this->harry->id]);

        Livewire::actingAs($this->kyle)
            ->test('pages::partner.search')
            ->assertSee('Harry declined your request')
            ->call('dismissOutgoing')
            ->assertDontSee('Harry declined your request');
    }

    public function test_the_partner_page_never_shows_other_users_emails(): void
    {
        CoupleRequest::factory()->create(['sender_id' => $this->sam->id, 'receiver_id' => $this->kyle->id]);

        $this->searchAs($this->kyle, 'harry')
            ->call('sendRequest')
            ->assertDontSee('harry@example.com')
            ->assertDontSee('sam@example.com');
    }

    // US3 ---------------------------------------------------------------

    public function test_a_single_user_is_redirected_from_the_couple_page_to_search(): void
    {
        $this->actingAs($this->kyle)
            ->get(route('couple.show'))
            ->assertRedirect(route('partner.search'));
    }

    public function test_the_couple_page_shows_the_partner_and_the_end_couple_warning(): void
    {
        Couple::factory()->withMembers($this->kyle, $this->harry)->create();

        $this->actingAs($this->kyle->refresh())
            ->get(route('couple.show'))
            ->assertOk()
            ->assertSee('data-test="couple-partner"', false)
            ->assertSee('Harry')
            ->assertSee('@harry')
            ->assertSee('data-test="end-couple-button"', false)
            ->assertSee('data-test="confirm-end-couple-button"', false)
            ->assertSee('End your couple with Harry?')
            ->assertSee("This will permanently delete all of your couple's reviews, for both of you. This cannot be undone.")
            ->assertDontSee('harry@example.com');
    }

    public function test_the_navigation_matches_the_users_couple_state(): void
    {
        $this->actingAs($this->kyle)
            ->get(route('partner.search'))
            ->assertSee('Find your partner')
            ->assertDontSee('Your partner');

        Couple::factory()->withMembers($this->kyle, $this->harry)->create();

        $this->actingAs($this->kyle->refresh())
            ->get(route('couple.show'))
            ->assertSee('Your partner')
            ->assertSee(route('couple.show'))
            ->assertDontSee('Find your partner');
    }
}
