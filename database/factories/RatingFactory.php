<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'rated_by' => User::factory(),
            'score' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional(0.5)->sentence(),
        ];
    }
}