<?php

namespace Database\Factories;

use App\Enums\ReviewCategory;
use App\Models\Couple;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'couple_id' => Couple::factory(),
            'category' => fake()->randomElement(ReviewCategory::cases()),
            'title' => fake()->unique()->words(3, true),
        ];
    }

    public function forCouple(Couple $couple): static
    {
        return $this->state(['couple_id' => $couple->id]);
    }
}
