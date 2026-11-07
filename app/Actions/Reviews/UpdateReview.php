<?php

namespace App\Actions\Reviews;

use App\Models\Review;
use Illuminate\Support\Facades\DB;

class UpdateReview
{
    /**
     * Change the stars and text of a review and mark it as edited (FR-009). Editing never
     * prompts the partner (FR-017).
     */
    public function __invoke(Review $review, string $stars, string $body): Review
    {
        return DB::transaction(function () use ($review, $stars, $body) {
            $review->forceFill([
                'rating' => Review::starsToRating($stars),
                'body' => trim($body),
                'edited_at' => now(),
            ])->save();

            return $review;
        });
    }
}
