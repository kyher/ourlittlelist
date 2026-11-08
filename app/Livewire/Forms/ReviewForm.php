<?php

namespace App\Livewire\Forms;

use App\Enums\ReviewCategory;
use App\Models\Review;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Shared field validation for adding and editing a review, so the rules cannot drift (FR-005, FR-006, FR-009).
 */
class ReviewForm extends Form
{
    /**
     * Starts as '' (not null) to match the select's placeholder option. With null nothing matches,
     * so the browser shows the first option (Film) as chosen while the value stays empty, and
     * picking Film then sends no change.
     */
    public ?string $category = '';

    public string $title = '';

    public ?string $stars = null;

    public string $body = '';

    /**
     * Validate a review of a new item: category, title, stars and body.
     *
     * @return array{category: string, title: string, stars: string, body: string}
     */
    public function validateNewItem(): array
    {
        $this->title = trim($this->title);
        $this->body = trim($this->body);

        /** @var array{category: string, title: string, stars: string, body: string} */
        return $this->validate([
            'category' => ['required', Rule::enum(ReviewCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            ...$this->reviewRules(),
        ], $this->messages());
    }

    /**
     * Validate only the stars and body, for an existing item or an edit.
     *
     * @return array{stars: string, body: string}
     */
    public function validateReview(): array
    {
        $this->body = trim($this->body);

        /** @var array{stars: string, body: string} */
        return $this->validate($this->reviewRules(), $this->messages());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function reviewRules(): array
    {
        return [
            'stars' => ['required', Rule::in(Review::STAR_VALUES)],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => __('Choose a category.'),
            'category.enum' => __('Choose a category.'),
            'title.required' => __('Enter a title.'),
            'title.max' => __('The title must be 255 characters or fewer.'),
            'stars.required' => __('Choose a rating from 0.5 to 5 stars.'),
            'stars.in' => __('Choose a rating from 0.5 to 5 stars.'),
            'body.required' => __('Write your review.'),
            'body.max' => __('Your review must be 5,000 characters or fewer.'),
        ];
    }
}
