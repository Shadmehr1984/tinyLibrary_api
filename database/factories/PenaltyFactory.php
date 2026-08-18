<?php

namespace Database\Factories;

use App\Models\Penalty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penalty>
 */
class PenaltyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'borrowed_id' => fake()->numberBetween(1, 80),
            'member_id' => fake()->numberBetween(1, 10),
            'amount' => fake()->numberBetween(1000, 20000),
            'calculated_at' => fake()->date(),
            'paid_at' => null
        ];
    }
}
