<?php

namespace App\Models;

class Driver extends User
{
    protected static function booted()
    {
        static::addGlobalScope('driver', fn ($query) => $query->where('role', 'driver'));
    }

    public static function scopeVisibleTo($query, User $user)
    {
        if ($user->role === 'admin' || $user->role === 'dispatcher') {
            return $query;
        }
        if ($user->role === 'driver') {
            return $query->where('id', $user->id);
        }
        return $query->whereRaw('1 = 0');
    }
}
