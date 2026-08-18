<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
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
            'address' => fake()->address(),
            'membership_date' => fake()->date(),
            'active' => true,
            'penalty_balance' => 0
        ];
    }
}
