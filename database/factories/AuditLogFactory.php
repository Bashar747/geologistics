<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['shipment.status_changed', 'user.login', 'vehicle.assigned', 'payment.processed']),
            'entity_type' => 'Shipment',
            'entity_id' => fake()->numberBetween(1, 100),
            'metadata' => ['ip' => fake()->ipv4(), 'note' => fake()->sentence()],
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}