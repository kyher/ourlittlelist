<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 10),
            'body' => fake()->paragraph(),
        ];
    }

    public function by(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function stars(float $stars): static
    {
        return $this->state(['rating' => Review::starsToRating($stars)]);
    }
}
