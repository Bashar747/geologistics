<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shipment $shipment): bool
    {
        if (in_array($user->role, ['admin', 'dispatcher'])) {
            return true;
        }
        if ($user->role === 'customer') {
            return $shipment->customer_id === $user->id;
        }
        if ($user->role === 'driver') {
            $vehicleIds = $user->vehicleAssignments()->where('is_active', true)->pluck('vehicle_id');
            return in_array($shipment->vehicle_id, $vehicleIds->toArray());
        }
        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'dispatcher', 'customer']);
    }

    public function update(User $user, Shipment $shipment): bool
    {
        if (in_array($user->role, ['admin', 'dispatcher'])) {
            return true;
        }
        if ($user->role === 'driver') {
            $vehicleIds = $user->vehicleAssignments()->where('is_active', true)->pluck('vehicle_id');
            return in_array($shipment->vehicle_id, $vehicleIds->toArray());
        }
        return false;
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }
}
