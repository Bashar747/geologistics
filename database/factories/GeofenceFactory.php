<?php

namespace Database\Factories;

use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Data\Geometries\Polygon;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeofenceFactory extends Factory
{
    public function definition(): array
    {
        $lat = fake()->latitude(31, 34);
        $lng = fake()->longitude(35, 37);
        $delta = 0.01;

        $ring = LineString::make([
            Point::make($lng - $delta, $lat - $delta, srid: 4326),
            Point::make($lng + $delta, $lat - $delta, srid: 4326),
            Point::make($lng + $delta, $lat + $delta, srid: 4326),
            Point::make($lng - $delta, $lat + $delta, srid: 4326),
            Point::make($lng - $delta, $lat - $delta, srid: 4326), // إغلاق الحلقة
        ], srid: 4326);

        return [
            'name' => fake()->randomElement(['مستودع الشمال', 'منطقة تسليم وسط البلد', 'منطقة محظورة - مطار']),
            'area_polygon' => Polygon::make([$ring], srid: 4326),
            'type' => fake()->randomElement(['warehouse', 'restricted_zone', 'delivery_area']),
        ];
    }
}