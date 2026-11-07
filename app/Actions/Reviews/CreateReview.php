<?php

namespace App\Actions\Reviews;

use App\Enums\ReviewCategory;
use App\Exceptions\ReviewActionException;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreateReview
{
    /**
     * Add the author's review of an item. Pass an existing $item (answering a prompt, or from
     * the item page), or a category and title for a new item. A matching item in the couple is
     * reused instead of duplicated (FR-004).
     *
     * Returns the review and whether the item already existed.
     *
     * @return array{0: Review, 1: bool}
     *
     * @throws ReviewActionException
     */
    public function __invoke(User $author, ?Item $item, ?ReviewCategory $category, ?string $title, string $stars, string $body): array
    {
        return DB::transaction(function () use ($author, $item, $category, $title, $stars, $body) {
            $author = User::query()->whereKey($author->id)->lockForUpdate()->first();
            $couple = $author?->couple;

            if (! $author || ! $couple) {
                throw new ReviewActionException(__("You're no longer in a couple."));
            }

            if ($item) {
                $item = Item::forCouple($couple)->whereKey($item->id)->lockForUpdate()->first();

                if (! $item) {
                    throw new ReviewActionException(__('This item no longer exists.'));
                }

                $itemExisted = true;
            } else {
                if (! $category || $title === null) {
                    throw new \InvalidArgumentException('A category and title are needed for a new item.');
                }

                // The unique (couple, category, title_key) index makes this safe in a race (research R4).
                $item = Item::createOrFirst(
                    ['couple_id' => $couple->id, 'category' => $category, 'title_key' => Item::keyFor($title)],
                    ['title' => $title, 'created_by' => $author->id],
                );
                $itemExisted = ! $item->wasRecentlyCreated;
                $item = Item::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            }

            if ($item->reviewBy($author)) {
                throw $this->alreadyReviewed($item);
            }

            try {
                /** @var Review $review */
                $review = $item->reviews()->create([
                    'user_id' => $author->id,
                    'rating' => Review::starsToRating($stars),
                    'body' => trim($body),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw $this->alreadyReviewed($item);
            }

            // Only the first review of an item prompts the partner (FR-013, FR-017, research R6).
            if (! $itemExisted && $partner = $author->partner()) {
                $item->prompts()->create(['user_id' => $partner->id]);
            }

            // Reviewing an item answers the author's own prompt for it.
            $item->prompts()->where('user_id', $author->id)->delete();

            return [$review, $itemExisted];
        });
    }

    private function alreadyReviewed(Item $item): ReviewActionException
    {
        return new ReviewActionException(__("You've already reviewed :title. Edit your review instead.", ['title' => $item->title]));
    }
}
