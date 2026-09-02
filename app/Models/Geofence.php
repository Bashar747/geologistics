<?php

namespace App\Models;

use Clickbar\Magellan\Data\Geometries\Polygon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Geofence extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'area_polygon',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'area_polygon' => Polygon::class,
        ];
    }
}