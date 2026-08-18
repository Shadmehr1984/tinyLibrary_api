<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->streetName(),
            'author' => fake()->name(),
            'isbn' => fake()->isbn13(),
            'published' => fake()->date(),
            'publisher' => fake()->name(),
            'category_id' => fake()->numberBetween(1, 20),
            'total_copies' => $total_copies = fake()->numberBetween(1, 20),
            'available_copies' => fake()->numberBetween(1, $total_copies),
            'description' => fake()->text(30),
            'location' => fake()->city(),
            'deleted_at' => null
        ];
    }
}
