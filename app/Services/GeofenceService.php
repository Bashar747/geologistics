<?php

namespace App\Services;

use App\Events\GeofenceEntered;
use App\Events\GeofenceExited;
use App\Models\Geofence;
use App\Models\Vehicle;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Database\PostgisFunctions\ST;
use Illuminate\Support\Facades\Cache;

class GeofenceService
{
    public function checkLocation(Vehicle $vehicle, float $latitude, float $longitude): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            return;
        }

        $point = Point::makeGeodetic($latitude, $longitude);

        $currentGeofences = Geofence::query()
            ->where(ST::contains('area_polygon', $point), true)
            ->get();

        $currentIds = $currentGeofences->pluck('id')->toArray();

        $cacheKey = "vehicle_geofences_{$vehicle->id}";
        $previousIds = Cache::get($cacheKey, []);

        $enteredIds = array_diff($currentIds, $previousIds);
        $exitedIds = array_diff($previousIds, $currentIds);

        Cache::put($cacheKey, $currentIds, now()->addDays(7));

        foreach ($currentGeofences->whereIn('id', $enteredIds) as $geofence) {
            event(new GeofenceEntered($vehicle, $geofence));
        }

        if (!empty($exitedIds)) {
            $exitedGeofences = Geofence::whereIn('id', $exitedIds)->get();
            foreach ($exitedGeofences as $geofence) {
                event(new GeofenceExited($vehicle, $geofence));
            }
        }
    }
}
