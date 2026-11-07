<?php

namespace App\Actions\Couples;

use App\Enums\CoupleRequestStatus;
use App\Exceptions\CoupleActionException;
use App\Models\Couple;
use App\Models\CoupleRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcceptCoupleRequest
{
    /**
     * The receiver accepts a pending request, creating a couple of exactly the two users
     * (FR-011 to FR-013).
     *
     * @throws CoupleActionException
     */
    public function __invoke(CoupleRequest $request): Couple
    {
        try {
            $couple = DB::transaction(function () use ($request) {
                $locked = CoupleRequest::query()->lockForUpdate()->findOrFail($request->id);

                if ($locked->status !== CoupleRequestStatus::Pending) {
                    throw new CoupleActionException(__('This request is no longer available.'));
                }

                $memberIds = [$locked->sender_id, $locked->receiver_id];
                $couple = Couple::create();

                // Atomic on every database: only users who are still single join the couple, so
                // concurrent accepts can never put the same user in two couples (research R2).
                $joined = User::query()
                    ->whereKey($memberIds)
                    ->whereNull('couple_id')
                    ->update(['couple_id' => $couple->id]);

                if ($joined !== 2) {
                    throw new AlreadyCoupled; // Rolls back the couple and any partial join.
                }

                $locked->resolve(CoupleRequestStatus::Accepted);

                // Close every other pending request sent by or to either member (FR-012).
                CoupleRequest::query()
                    ->pending()
                    ->where(fn ($query) => $query->whereIn('sender_id', $memberIds)->orWhereIn('receiver_id', $memberIds))
                    ->update(['status' => CoupleRequestStatus::Closed, 'responded_at' => now()]);

                return $couple;
            });
        } catch (AlreadyCoupled) {
            $this->refuseBecauseAlreadyCoupled($request);
        }

        $request->refresh();

        return $couple;
    }

    /**
     * One of the users joined another couple first: close the request and say why.
     *
     * @throws CoupleActionException
     */
    private function refuseBecauseAlreadyCoupled(CoupleRequest $request): never
    {
        CoupleRequest::query()->pending()->whereKey($request->id)
            ->update(['status' => CoupleRequestStatus::Closed, 'responded_at' => now()]);

        $request->refresh();
        $sender = $request->sender()->firstOrFail();
        $coupled = $sender->isInCouple() ? $sender : $request->receiver()->firstOrFail();

        throw new CoupleActionException(__(':name is already in a couple.', ['name' => $coupled->name]));
    }
}
