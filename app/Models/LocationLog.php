<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Clickbar\Magellan\Data\Geometries\Point;

class LocationLog extends Model
{
    use HasFactory;

    public $timestamps = false; // بس عندنا recorded_at، ما في created_at/updated_at

    protected $fillable = [
        'vehicle_id',
        'location',
        'speed',
        'heading',
        'recorded_at',
    ];

 protected function casts(): array
{
    return [
        'speed' => 'decimal:2',
        'recorded_at' => 'datetime',
        'location' => Point::class,
    ];
}
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}