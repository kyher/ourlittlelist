<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ReviewPrompt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewPrompt>
 */
class ReviewPromptFactory extends Factory
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
        ];
    }

    public function recipient(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function dismissed(): static
    {
        return $this->state(['dismissed_at' => now()]);
    }
}
