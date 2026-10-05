<?php

namespace App\Policies;

use App\Models\Vehicle;
use App\Models\User;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        if (in_array($user->role, ['admin', 'dispatcher'])) {
            return true;
        }
        if ($user->role === 'driver') {
            return $user->vehicleAssignments()->where('is_active', true)->where('vehicle_id', $vehicle->id)->exists();
        }
        if ($user->role === 'customer') {
            return $vehicle->shipments()->where('customer_id', $user->id)->exists();
        }
        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }
}
