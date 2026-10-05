<?php

namespace App\Listeners;

use App\Events\GeofenceEntered;
use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use App\Services\AuditLogger;

class HandleGeofenceEntered
{
    public function handle(GeofenceEntered $event): void
    {
        $vehicle = $event->vehicle;
        $geofence = $event->geofence;

        AuditLogger::log(
            'geofence.entered',
            'Geofence',
            $geofence->id,
            "Vehicle {$vehicle->plate_number} entered geofence {$geofence->name} ({$geofence->type})"
        );

        $activeShipments = Shipment::where('vehicle_id', $vehicle->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->get();

        foreach ($activeShipments as $shipment) {
            if ($geofence->type === 'delivery_area' && in_array($shipment->status, ['assigned', 'picked_up'])) {
                $shipment->update(['status' => 'delivered']);

                ShipmentStatusHistory::create([
                    'shipment_id' => $shipment->id,
                    'status' => 'delivered',
                    'changed_by' => null,
                    'note' => "Auto-delivered upon entering geofence: {$geofence->name}",
                ]);

                AuditLogger::log(
                    'shipment.auto_delivered',
                    'Shipment',
                    $shipment->id,
                    "Shipment automatically marked as delivered inside geofence {$geofence->name}"
                );
            }
        }
    }
}
