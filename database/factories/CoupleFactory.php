<?php

namespace Database\Factories;

use App\Models\Couple;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Couple>
 */
class CoupleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }

    /**
     * Make the given two users the members of the couple.
     */
    public function withMembers(User $a, User $b): static
    {
        return $this->afterCreating(function (Couple $couple) use ($a, $b) {
            foreach ([$a, $b] as $user) {
                $user->forceFill(['couple_id' => $couple->id])->save();
            }
        });
    }
}
