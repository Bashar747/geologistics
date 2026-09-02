<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'status' => fake()->randomElement(['pending', 'assigned', 'picked_up', 'delivered', 'cancelled']),
            'changed_by' => User::factory(),
            'note' => fake()->optional(0.3)->sentence(),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}