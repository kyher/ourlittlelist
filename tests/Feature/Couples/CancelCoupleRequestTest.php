<?php

namespace Tests\Feature\Couples;

use App\Actions\Couples\CancelCoupleRequest;
use App\Enums\CoupleRequestStatus;
use App\Exceptions\CoupleActionException;
use App\Models\CoupleRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CancelCoupleRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sender_can_cancel_a_pending_request(): void
    {
        $request = CoupleRequest::factory()->create();

        app(CancelCoupleRequest::class)($request);

        $request->refresh();
        $this->assertSame(CoupleRequestStatus::Cancelled, $request->status);
        $this->assertNotNull($request->responded_at);
    }

    public function test_a_request_that_is_not_pending_cannot_be_cancelled(): void
    {
        $request = CoupleRequest::factory()->declined()->create();

        $this->expectException(CoupleActionException::class);
        $this->expectExceptionMessage('This request is no longer available.');

        app(CancelCoupleRequest::class)($request);
    }

    public function test_the_sender_can_cancel_from_the_partner_page(): void
    {
        $request = CoupleRequest::factory()->create();

        Livewire::actingAs($request->sender)
            ->test('pages::partner.search')
            ->call('cancelRequest', $request->id)
            ->assertHasNoErrors();

        $this->assertSame(CoupleRequestStatus::Cancelled, $request->refresh()->status);
    }

    public function test_only_the_sender_can_cancel_a_request(): void
    {
        $request = CoupleRequest::factory()->create();

        foreach ([$request->receiver, User::factory()->create()] as $user) {
            Livewire::actingAs($user)
                ->test('pages::partner.search')
                ->call('cancelRequest', $request->id)
                ->assertForbidden();
        }

        $this->assertSame(CoupleRequestStatus::Pending, $request->refresh()->status);
    }
}
