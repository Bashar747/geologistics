<?php

namespace App\Observers;

use App\Models\DriverVehicleAssignment;
use App\Services\AuditLogger;

class DriverVehicleAssignmentObserver
{
    public function created(DriverVehicleAssignment $assignment): void
    {
        AuditLogger::log(
            'vehicle.assigned',
            'Vehicle',
            $assignment->vehicle_id,
            'Driver #' . $assignment->driver_id . ' assigned'
        );
    }

    public function updated(DriverVehicleAssignment $assignment): void
    {
        if ($assignment->isDirty('is_active') && ! $assignment->is_active) {
            AuditLogger::log(
                'vehicle.unassigned',
                'Vehicle',
                $assignment->vehicle_id,
                'Driver #' . $assignment->driver_id . ' unassigned'
            );
        }
    }
}