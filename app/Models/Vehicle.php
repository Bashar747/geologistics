<?php

namespace App\Models;

use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plate_number',
        'model',
        'type',
        'status',
        'last_location',
    ];

    protected function casts(): array
    {
        return [
            'last_location' => Point::class,
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverVehicleAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(DriverVehicleAssignment::class)->where('is_active', true);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function locationLogs(): HasMany
    {
        return $this->hasMany(LocationLog::class);
    }
}