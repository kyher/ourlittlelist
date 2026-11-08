<?php

namespace App\Policies;

use App\Models\Couple;
use App\Models\User;

class CouplePolicy
{
    /**
     * Only a member of the couple can end it.
     */
    public function end(User $user, Couple $couple): bool
    {
        return $user->couple_id === $couple->id;
    }
}
