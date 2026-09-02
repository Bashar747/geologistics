<?php

namespace Database\Factories;

use App\Models\User;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tracking_number' => 'GL-' . strtoupper(fake()->unique()->bothify('##########')),
            'customer_id' => User::factory(), // role الافتراضي customer
            'vehicle_id' => null, // يتحدد لاحقاً بحالة "assigned"
            'pickup_location' => Point::makeGeodetic(
                fake()->latitude(31, 34),
                fake()->longitude(35, 37)
            ),
            'dropoff_location' => Point::makeGeodetic(
                fake()->latitude(31, 34),
                fake()->longitude(35, 37)
            ),
            'status' => 'pending',
            'estimated_arrival' => fake()->dateTimeBetween('now', '+2 days'),
            'total_amount' => fake()->randomFloat(2, 10, 500),
        ];
    }

    // حالة: شحنة تم تخصيص مركبة لها
    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
            'vehicle_id' => \App\Models\Vehicle::factory(),
        ]);
    }

    // حالة: شحنة تم تسليمها
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'vehicle_id' => \App\Models\Vehicle::factory(),
        ]);
    }

    // حالة: شحنة ملغاة
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}