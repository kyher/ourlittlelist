<?php

namespace App\Policies;

use App\Models\CoupleRequest;
use App\Models\User;

class CoupleRequestPolicy
{
    /**
     * Only the receiver can accept a request.
     */
    public function accept(User $user, CoupleRequest $request): bool
    {
        return $user->id === $request->receiver_id;
    }

    /**
     * Only the receiver can decline a request.
     */
    public function decline(User $user, CoupleRequest $request): bool
    {
        return $user->id === $request->receiver_id;
    }

    /**
     * Only the sender can cancel a request.
     */
    public function cancel(User $user, CoupleRequest $request): bool
    {
        return $user->id === $request->sender_id;
    }
}
