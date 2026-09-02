<?php

namespace Database\Factories;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plate_number' => strtoupper(fake()->unique()->bothify('###-???')),
            'model' => fake()->randomElement(['Toyota Hiace', 'Mercedes Sprinter', 'Isuzu NPR', 'Ford Transit']),
            'type' => fake()->randomElement(['van', 'truck', 'pickup']),
            'status' => fake()->randomElement(['idle', 'in_transit', 'maintenance', 'offline']),
            // إحداثيات عشوائية تقريباً حوالين بيروت/عمّان كمثال (خليها متوافقة مع منطقتك)
            'last_location' => Point::makeGeodetic(
                fake()->latitude(31, 34),
                fake()->longitude(35, 37)
            ),
        ];
    }
}