<?php

namespace Database\Factories;

use App\Models\Librarian;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Librarian>
 */
class LibrarianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->email(),
            'password' => Hash::make(fake()->password(8, 10)),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address()
        ];
    }
}
