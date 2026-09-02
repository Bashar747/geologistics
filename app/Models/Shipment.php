<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Clickbar\Magellan\Data\Geometries\Point;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tracking_number',
        'customer_id',
        'vehicle_id',
        'pickup_location',
        'dropoff_location',
        'status',
        'estimated_arrival',
        'total_amount',
    ];

  protected function casts(): array
{
    return [
        'estimated_arrival' => 'datetime',
        'total_amount' => 'decimal:2',
        'pickup_location' => Point::class,
        'dropoff_location' => Point::class,
    ];
}

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ShipmentStatusHistory::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }
}