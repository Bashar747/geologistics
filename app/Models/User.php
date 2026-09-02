<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable
{
    use HasFactory,HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // علاقة 1:1 — بيانات السائق التفصيلية (لو كان role = driver)
    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    // كل تخصيصات المركبات (لو كان سائق)
    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(DriverVehicleAssignment::class, 'driver_id');
    }

    // الشحنات كعميل
    public function shipmentsAsCustomer(): HasMany
    {
        return $this->hasMany(Shipment::class, 'customer_id');
    }
}