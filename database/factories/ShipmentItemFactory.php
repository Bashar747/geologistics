<?php

namespace Database\Factories;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'description' => fake()->randomElement([
                'صندوق ملابس', 'أجهزة إلكترونية', 'أثاث منزلي', 'مستندات', 'قطع غيار سيارات',
            ]),
            'weight_kg' => fake()->randomFloat(2, 0.5, 50),
            'dimensions' => fake()->numberBetween(10, 100) . 'x' . fake()->numberBetween(10, 100) . 'x' . fake()->numberBetween(10, 100),
            'quantity' => fake()->numberBetween(1, 5),
            'fragile' => fake()->boolean(20), // 20% احتمال يكون قابل للكسر
        ];
    }
}