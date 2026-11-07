<?php

namespace Tests\Feature\Couples;

use App\Actions\Couples\SendCoupleRequest;
use App\Enums\CoupleRequestStatus;
use App\Exceptions\CoupleActionException;
use App\Models\Couple;
use App\Models\CoupleRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SendCoupleRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $kyle;

    private User $harry;

    private User $sam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kyle = User::factory()->create(['name' => 'Kyle', 'username' => 'kyle']);
        $this->harry = User::factory()->create(['name' => 'Harry', 'username' => 'harry']);
        $this->sam = User::factory()->create(['name' => 'Sam', 'username' => 'sam']);

        RateLimiter::clear('couple-request:'.$this->kyle->id);
    }

    private function send(User $sender, User $receiver): CoupleRequest
    {
        return app(SendCoupleRequest::class)($sender, $receiver);
    }

    private function assertRefused(User $sender, User $receiver, string $message): void
    {
        $before = CoupleRequest::count();

        try {
            $this->send($sender, $receiver);
            $this->fail('Expected the request to be refused.');
        } catch (CoupleActionException $e) {
            $this->assertSame($message, $e->getMessage());
        }

        $this->assertSame($before, CoupleRequest::count());
    }

    public function test_a_single_user_can_send_a_request_to_another_single_user(): void
    {
        $request = $this->send($this->kyle, $this->harry);

        $this->assertSame(CoupleRequestStatus::Pending, $request->status);
        $this->assertDatabaseHas('couple_requests', [
            'sender_id' => $this->kyle->id,
            'receiver_id' => $this->harry->id,
            'status' => 'pending',
        ]);
    }

    public function test_a_user_cannot_send_a_request_to_themselves(): void
    {
        $this->assertRefused($this->kyle, $this->kyle, "You can't send a request to yourself.");
    }

    public function test_a_user_in_a_couple_cannot_send_a_request(): void
    {
        Couple::factory()->withMembers($this->kyle, $this->sam)->create();

        $this->assertRefused($this->kyle->refresh(), $this->harry, "You're already in a couple.");
    }

    public function test_a_request_cannot_be_sent_to_a_user_in_a_couple(): void
    {
        Couple::factory()->withMembers($this->harry, $this->sam)->create();

        $this->assertRefused($this->kyle, $this->harry->refresh(), 'Harry is not available.');
    }

    public function test_a_user_can_only_have_one_pending_outgoing_request(): void
    {
        $this->send($this->kyle, $this->harry);

        $this->assertRefused($this->kyle, $this->sam, 'You already have a pending request. Cancel it first.');
        $this->assertRefused($this->kyle, $this->harry, 'You already have a pending request. Cancel it first.');
    }

    public function test_a_user_is_pointed_to_an_existing_incoming_request_instead_of_creating_a_duplicate(): void
    {
        $this->send($this->harry, $this->kyle);

        $this->assertRefused($this->kyle, $this->harry, 'Harry has already sent you a request. Accept it below.');
        $this->assertSame(1, CoupleRequest::count());
    }

    public function test_a_user_can_send_again_after_being_declined(): void
    {
        CoupleRequest::factory()->declined()->create([
            'sender_id' => $this->kyle->id,
            'receiver_id' => $this->harry->id,
        ]);

        $this->send($this->kyle, $this->harry);

        $this->assertSame(1, CoupleRequest::query()->pending()->count());
    }

    public function test_requests_are_rate_limited_to_ten_per_hour(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->send($this->kyle, $this->harry)->resolve(CoupleRequestStatus::Cancelled);
        }

        $this->assertRefused($this->kyle, $this->harry, "You've sent too many requests. Try again in 60 minutes.");
    }
}
