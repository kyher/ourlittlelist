<?php

namespace Database\Factories;

use App\Enums\CoupleRequestStatus;
use App\Models\CoupleRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoupleRequest>
 */
class CoupleRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'status' => CoupleRequestStatus::Pending,
        ];
    }

    public function declined(): static
    {
        return $this->withStatus(CoupleRequestStatus::Declined);
    }

    public function cancelled(): static
    {
        return $this->withStatus(CoupleRequestStatus::Cancelled);
    }

    public function accepted(): static
    {
        return $this->withStatus(CoupleRequestStatus::Accepted);
    }

    public function closed(): static
    {
        return $this->withStatus(CoupleRequestStatus::Closed);
    }

    private function withStatus(CoupleRequestStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'responded_at' => now(),
        ]);
    }
}
