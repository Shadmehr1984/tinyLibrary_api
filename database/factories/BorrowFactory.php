<?php

namespace Database\Factories;

use App\Models\Borrow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrow>
 */
class BorrowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => fake()->numberBetween(1, 10),
            'book_id' => fake()->numberBetween(1, 300),
            'borrowed_at' => fake()->date(),
            'due_date' => null,
            'returned_at' => fake()->numberBetween(1, 6)  == 1 ? null : fake()->date(),
            'status' => fake()->randomElement( ['borrowed', 'returned', 'overdue']),
            'penalty_amount' => 0
        ];
    }
}
