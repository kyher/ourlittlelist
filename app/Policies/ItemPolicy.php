<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Only members of the item's couple can see it (Principle III).
     */
    public function view(User $user, Item $item): bool
    {
        return $this->isMember($user, $item);
    }

    /**
     * Only members of the item's couple can review it.
     */
    public function review(User $user, Item $item): bool
    {
        return $this->isMember($user, $item);
    }

    private function isMember(User $user, Item $item): bool
    {
        return $user->couple_id !== null && $user->couple_id === $item->couple_id;
    }
}
