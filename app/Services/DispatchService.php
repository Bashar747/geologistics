<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    public function suggestVehicle(Shipment $shipment): ?array
    {
        if (! $shipment->pickup_location) {
            return null;
        }

        $vehicle = DB::table('vehicles')
            ->join('driver_vehicle_assignments', function ($join) {
                $join->on(
                    'driver_vehicle_assignments.vehicle_id',
                    '=',
                    'vehicles.id'
                )->where('driver_vehicle_assignments.is_active', true);
            })
            ->join('users', function ($join) {
                $join->on(
                    'users.id',
                    '=',
                    'driver_vehicle_assignments.driver_id'
                )->where('users.role', 'driver');
            })
            ->join('driver_profiles', function ($join) {
                $join->on(
                    'driver_profiles.user_id',
                    '=',
                    'users.id'
                )->where('driver_profiles.status', 'available');
            })
            ->where('vehicles.status', 'idle')
            ->whereNotNull('vehicles.last_location')
            ->select([
                'vehicles.id',
                'vehicles.plate_number',
                'vehicles.model',
                'users.name as driver_name',
            ])
            ->selectRaw(
                'ST_Distance(
                    vehicles.last_location,
                    ?::geography
                ) as distance_meters',
                [$shipment->pickup_location]
            )
            ->orderBy('distance_meters')
            ->first();

        if (! $vehicle) {
            return null;
        }

        return [
            'vehicle_id' => $vehicle->id,
            'plate_number' => $vehicle->plate_number,
            'model' => $vehicle->model,
            'driver_name' => $vehicle->driver_name,
            'distance_km' => round(
                ((float) $vehicle->distance_meters) / 1000,
                2
            ),
        ];
    }
}