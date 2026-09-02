<?php

namespace Database\Factories;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'method' => fake()->randomElement(['cash', 'card', 'wallet']),
            'status' => fake()->randomElement(['pending', 'paid', 'refunded', 'failed']),
            'paid_at' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'),
        ];
    }
}