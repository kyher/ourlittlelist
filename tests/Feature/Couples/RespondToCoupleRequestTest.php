<?php

namespace Tests\Feature\Couples;

use App\Actions\Couples\AcceptCoupleRequest;
use App\Actions\Couples\DeclineCoupleRequest;
use App\Actions\Couples\SendCoupleRequest;
use App\Enums\CoupleRequestStatus;
use App\Exceptions\CoupleActionException;
use App\Models\Couple;
use App\Models\CoupleRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RespondToCoupleRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $kyle;

    private User $harry;

    private User $sam;

    private CoupleRequest $request;

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

        $this->request = CoupleRequest::factory()->create([
            'sender_id' => $this->kyle->id,
            'receiver_id' => $this->harry->id,
        ]);
    }

    private function accept(): Couple
    {
        return app(AcceptCoupleRequest::class)($this->request);
    }

    public function test_accepting_creates_a_couple_of_exactly_the_two_users(): void
    {
        $couple = $this->accept();

        $this->assertSame(1, Couple::count());
        $this->assertSame($couple->id, $this->kyle->refresh()->couple_id);
        $this->assertSame($couple->id, $this->harry->refresh()->couple_id);
        $this->assertSame(2, $couple->members()->count());
        $this->assertTrue($this->kyle->partner()->is($this->harry));
        $this->assertSame(CoupleRequestStatus::Accepted, $this->request->status);
        $this->assertNotNull($this->request->responded_at);
    }

    public function test_accepting_closes_every_other_pending_request_for_both_users(): void
    {
        $toHarry = CoupleRequest::factory()->create(['sender_id' => $this->sam->id, 'receiver_id' => $this->harry->id]);
        $harrysOwn = CoupleRequest::factory()->create(['sender_id' => $this->harry->id, 'receiver_id' => $this->sam->id]);
        $toKyle = CoupleRequest::factory()->create(['sender_id' => User::factory()->create()->id, 'receiver_id' => $this->kyle->id]);
        $unrelated = CoupleRequest::factory()->create();

        $couple = $this->accept();

        $this->assertSame(CoupleRequestStatus::Closed, $toHarry->refresh()->status);
        $this->assertSame(CoupleRequestStatus::Closed, $harrysOwn->refresh()->status);
        $this->assertSame(CoupleRequestStatus::Closed, $toKyle->refresh()->status);
        $this->assertSame(CoupleRequestStatus::Pending, $unrelated->refresh()->status);
        $this->assertSame(2, $couple->members()->count());
        $this->assertNull($this->sam->refresh()->couple_id);
    }

    public function test_declining_creates_no_couple_and_lets_the_sender_ask_again(): void
    {
        app(DeclineCoupleRequest::class)($this->request);

        $this->assertSame(CoupleRequestStatus::Declined, $this->request->status);
        $this->assertSame(0, Couple::count());
        $this->assertNull($this->kyle->refresh()->couple_id);
        $this->assertNull($this->harry->refresh()->couple_id);

        app(SendCoupleRequest::class)($this->kyle, $this->harry);
        $this->assertSame(1, CoupleRequest::query()->pending()->count());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function finalStatuses(): array
    {
        return ['cancelled' => ['cancelled'], 'declined' => ['declined'], 'closed' => ['closed'], 'accepted' => ['accepted']];
    }

    #[DataProvider('finalStatuses')]
    public function test_a_request_that_is_no_longer_pending_cannot_be_answered(string $status): void
    {
        $this->request->resolve(CoupleRequestStatus::from($status));

        foreach ([AcceptCoupleRequest::class, DeclineCoupleRequest::class] as $action) {
            try {
                app($action)($this->request);
                $this->fail('Expected the response to be refused.');
            } catch (CoupleActionException $e) {
                $this->assertSame('This request is no longer available.', $e->getMessage());
            }
        }

        $this->assertSame(0, Couple::count());
    }

    public function test_accepting_fails_when_the_sender_has_joined_another_couple(): void
    {
        $existing = Couple::factory()->withMembers($this->kyle, $this->sam)->create();

        try {
            $this->accept();
            $this->fail('Expected the accept to be refused.');
        } catch (CoupleActionException $e) {
            $this->assertSame('Kyle is already in a couple.', $e->getMessage());
        }

        $this->assertSame(1, Couple::count());
        $this->assertSame($existing->id, $this->kyle->refresh()->couple_id);
        $this->assertNull($this->harry->refresh()->couple_id);
        $this->assertSame(CoupleRequestStatus::Closed, $this->request->refresh()->status);
    }

    public function test_accepting_fails_when_the_receiver_has_joined_another_couple(): void
    {
        Couple::factory()->withMembers($this->harry, $this->sam)->create();

        try {
            $this->accept();
            $this->fail('Expected the accept to be refused.');
        } catch (CoupleActionException $e) {
            $this->assertSame('Harry is already in a couple.', $e->getMessage());
        }

        $this->assertSame(1, Couple::count());
        $this->assertNull($this->kyle->refresh()->couple_id);
    }

    public function test_only_the_receiver_can_accept_or_decline(): void
    {
        foreach ([$this->kyle, $this->sam] as $user) {
            foreach (['acceptRequest', 'declineRequest'] as $method) {
                Livewire::actingAs($user)
                    ->test('pages::partner.search')
                    ->call($method, $this->request->id)
                    ->assertForbidden();
            }
        }

        $this->assertSame(CoupleRequestStatus::Pending, $this->request->refresh()->status);
        $this->assertSame(0, Couple::count());
    }

    public function test_the_receiver_sees_incoming_requests_without_the_senders_email(): void
    {
        Livewire::actingAs($this->harry)
            ->test('pages::partner.search')
            ->assertSeeHtml('data-test="incoming-request"')
            ->assertSee('Kyle')
            ->assertSee('@kyle')
            ->assertDontSee('kyle@example.com');
    }

    public function test_accepting_from_the_page_redirects_to_the_couple(): void
    {
        Livewire::actingAs($this->harry)
            ->test('pages::partner.search')
            ->call('acceptRequest', $this->request->id)
            ->assertRedirect(route('couple.show'));

        $this->assertNotNull($this->harry->refresh()->couple_id);
    }

    public function test_declining_from_the_page_removes_the_request(): void
    {
        Livewire::actingAs($this->harry)
            ->test('pages::partner.search')
            ->call('declineRequest', $this->request->id)
            ->assertDontSeeHtml('data-test="incoming-request"');

        $this->assertSame(CoupleRequestStatus::Declined, $this->request->refresh()->status);
    }

    public function test_a_search_result_for_someone_who_asked_you_offers_accept_and_reject(): void
    {
        Livewire::actingAs($this->harry)
            ->test('pages::partner.search')
            ->set('query', 'kyle')
            ->call('search')
            ->assertSee('Kyle has sent you a request')
            ->assertSet('result.relation', 'pending_incoming')
            ->assertSet('result.request_id', $this->request->id);
    }

    public function test_the_sender_sees_when_the_receiver_is_no_longer_available(): void
    {
        $this->request->resolve(CoupleRequestStatus::Closed);

        Livewire::actingAs($this->kyle)
            ->test('pages::partner.search')
            ->assertSee('Harry is no longer available');
    }
}
