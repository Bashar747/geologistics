<?php

namespace App\Policies;

use App\Models\Geofence;
use App\Models\User;

class GeofencePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }

    public function view(User $user, Geofence $geofence): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }

    public function update(User $user, Geofence $geofence): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }

    public function delete(User $user, Geofence $geofence): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }
}
