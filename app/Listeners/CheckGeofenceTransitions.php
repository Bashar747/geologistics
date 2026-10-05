<?php

namespace App\Listeners;

use App\Events\VehicleLocationUpdated;
use App\Services\GeofenceService;

class CheckGeofenceTransitions
{
    public function __construct(protected GeofenceService $geofenceService)
    {
    }

    public function handle(VehicleLocationUpdated $event): void
    {
        $vehicle = $event->vehicle;

        if (!$vehicle->last_location) {
            return;
        }

        $lat = $vehicle->last_location->getLatitude();
        $lng = $vehicle->last_location->getLongitude();

        $this->geofenceService->checkLocation($vehicle, $lat, $lng);
    }
}
