<?php

namespace App\Observers;

use App\Models\SentNotification;
use App\Models\Shipment;
use App\Services\AuditLogger;

class ShipmentObserver
{
    public function updated(Shipment $shipment): void
    {
        if (! $shipment->wasChanged('status')) {
            return;
        }

        $oldStatus = $shipment->getOriginal('status');
        $newStatus = $shipment->status;

        AuditLogger::log(
            'shipment.status_changed',
            'Shipment',
            $shipment->id,
            "Status changed from {$oldStatus} to {$newStatus}"
        );

        $this->sendStatusNotification($shipment, $newStatus);
    }

    private function sendStatusNotification(Shipment $shipment, string $status): void
    {
        $shipment->loadMissing([
            'customer:id,name',
            'vehicle.currentAssignment.driver:id,name',
        ]);

        $messages = [
            'assigned' => "Shipment {$shipment->tracking_number} has been assigned to a vehicle.",
            'picked_up' => "Shipment {$shipment->tracking_number} has been picked up.",
            'in_transit' => "Shipment {$shipment->tracking_number} is now in transit.",
            'delivered' => "Shipment {$shipment->tracking_number} has been delivered.",
            'cancelled' => "Shipment {$shipment->tracking_number} has been cancelled.",
        ];

        if (! isset($messages[$status])) {
            return;
        }

        // Notify customer
        if ($shipment->customer) {
            SentNotification::create([
                'user_id' => $shipment->customer->id,
                'channel' => 'push',
                'message' => $messages[$status],
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        // Notify driver when shipment is assigned
        if ($status === 'assigned') {
            $driver = $shipment->vehicle?->currentAssignment?->driver;

            if ($driver) {
                SentNotification::create([
                    'user_id' => $driver->id,
                    'channel' => 'push',
                    'message' => "Shipment {$shipment->tracking_number} has been assigned to you.",
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }
        }
    }
}