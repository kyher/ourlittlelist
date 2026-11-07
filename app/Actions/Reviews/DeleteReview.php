<?php

namespace App\Actions\Reviews;

use App\Models\Item;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class DeleteReview
{
    /**
     * Permanently remove a review. When it was the item's last review, the item and its
     * prompts go too (FR-010, FR-011). Returns whether the item was removed.
     */
    public function __invoke(Review $review): bool
    {
        return DB::transaction(function () use ($review) {
            $item = Item::query()->whereKey($review->item_id)->lockForUpdate()->first();

            $review->delete();

            if ($item && $item->reviews()->doesntExist()) {
                $item->delete();

                return true;
            }

            return $item === null;
        });
    }
}
