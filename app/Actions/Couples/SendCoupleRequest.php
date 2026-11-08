<?php

namespace App\Actions\Couples;

use App\Enums\CoupleRequestStatus;
use App\Exceptions\CoupleActionException;
use App\Models\CoupleRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class SendCoupleRequest
{
    /**
     * Maximum successful requests one user can send per hour (FR-018).
     */
    public const MAX_PER_HOUR = 10;

    /**
     * Send a couple request from one single user to another (FR-004 to FR-007).
     *
     * @throws CoupleActionException
     */
    public function __invoke(User $sender, User $receiver): CoupleRequest
    {
        if ($sender->is($receiver)) {
            throw new CoupleActionException(__("You can't send a request to yourself."));
        }

        return DB::transaction(function () use ($sender, $receiver) {
            // Lock both users in id order so competing sends between them are serialised.
            User::query()->whereKey([$sender->id, $receiver->id])->orderBy('id')->lockForUpdate()->get();
            $sender->refresh();
            $receiver->refresh();

            if ($sender->isInCouple()) {
                throw new CoupleActionException(__("You're already in a couple."));
            }

            if ($receiver->isInCouple()) {
                throw new CoupleActionException(__(':name is not available.', ['name' => $receiver->name]));
            }

            if ($receiver->sentCoupleRequests()->pending()->where('receiver_id', $sender->id)->exists()) {
                throw new CoupleActionException(__(':name has already sent you a request. Accept it below.', ['name' => $receiver->name]));
            }

            if ($sender->sentCoupleRequests()->pending()->exists()) {
                throw new CoupleActionException(__('You already have a pending request. Cancel it first.'));
            }

            $key = 'couple-request:'.$sender->id;

            if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
                throw new CoupleActionException(__("You've sent too many requests. Try again in :minutes minutes.", [
                    'minutes' => (int) ceil(RateLimiter::availableIn($key) / 60),
                ]));
            }

            $request = $sender->sentCoupleRequests()->create([
                'receiver_id' => $receiver->id,
                'status' => CoupleRequestStatus::Pending,
            ]);

            RateLimiter::hit($key, 3600);

            return $request;
        });
    }
}
