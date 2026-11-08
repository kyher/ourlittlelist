<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Only the author can edit their review; a partner's review is read-only (FR-012).
     */
    public function update(User $user, Review $review): bool
    {
        return $this->isAuthorInCouple($user, $review);
    }

    /**
     * Only the author can remove their review (FR-012).
     */
    public function delete(User $user, Review $review): bool
    {
        return $this->isAuthorInCouple($user, $review);
    }

    private function isAuthorInCouple(User $user, Review $review): bool
    {
        return $review->user_id === $user->id
            && $user->couple_id !== null
            && $user->couple_id === $review->item->couple_id;
    }
}
