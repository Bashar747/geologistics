<?php

namespace App\Observers;

use App\Models\Shipment;
use App\Services\AuditLogger;

class ShipmentObserver
{
    public function updated(Shipment $shipment): void
    {
        if ($shipment->isDirty('status')) {
            AuditLogger::log(
                'shipment.status_changed',
                'Shipment',
                $shipment->id,
                'Status changed to ' . $shipment->status
            );
        }
    }
}