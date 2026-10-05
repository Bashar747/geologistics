<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'amount',
        'method',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->role === 'admin' || $user->role === 'dispatcher') {
            return $query;
        }

        if ($user->role === 'customer') {
            return $query->whereHas('shipment', fn($q) => $q->where('customer_id', $user->id));
        }

        if ($user->role === 'driver') {
            return $query->whereHas('shipment', function($q) use ($user) {
                $vehicleIds = $user->vehicleAssignments()->where('is_active', true)->pluck('vehicle_id');
                $q->whereIn('vehicle_id', $vehicleIds);
            });
        }

        return $query->whereRaw('1 = 0');
    }
}