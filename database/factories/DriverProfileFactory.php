<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->driver(),
            'license_number' => fake()->unique()->bothify('DL-#####'),
            'license_expiry' => fake()->dateTimeBetween('+6 months', '+3 years'),
            'national_id' => fake()->unique()->numerify('##########'),
            'rating_avg' => fake()->randomFloat(2, 3, 5),
            'status' => fake()->randomElement(['available', 'on_duty', 'suspended']),
            'joined_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}