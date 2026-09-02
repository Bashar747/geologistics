<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'location' => Point::makeGeodetic(
                fake()->latitude(31, 34),
                fake()->longitude(35, 37)
            ),
            'speed' => fake()->randomFloat(2, 0, 120),
            'heading' => fake()->numberBetween(0, 359),
            'recorded_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ];
    }
}