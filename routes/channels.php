<?php

use App\Models\Shipment;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('vehicle.{vehicleId}', function ($user, int $vehicleId) {
    // أدمن وموزّع يقدروا يتتبعوا أي مركبة
    if (in_array($user->role, ['admin', 'dispatcher'], true)) {
        return true;
    }

    // السائق المخصص فعلياً لهاي المركبة (تخصيص نشط)
    if ($user->role === 'driver') {
        $activeVehicleId = $user->vehicleAssignments()
            ->where('is_active', true)
            ->value('vehicle_id');

        return $activeVehicleId === $vehicleId;
    }

    // العميل يقدر يتتبع بس إذا عنده شحنة نشطة (assigned/picked_up) بهاي المركبة بالضبط
    if ($user->role === 'customer') {
        return Shipment::where('customer_id', $user->id)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['assigned', 'picked_up'])
            ->exists();
    }

    return false;
});