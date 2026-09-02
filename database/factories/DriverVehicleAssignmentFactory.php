<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverVehicleAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'driver_id' => User::factory()->driver(),
            'vehicle_id' => Vehicle::factory(),
            'assigned_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'unassigned_at' => null,
            'is_active' => true,
        ];
    }
}