<?php

use App\Models\Shipment;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('vehicle.{vehicleId}', function ($user, int $vehicleId) {
    // الأدمن والموزع يقدروا يتتبعوا أي مركبة
    if (in_array($user->role, ['admin', 'dispatcher'], true)) {
        return true;
    }

    // السائق المخصص فعلياً لهذه المركبة
    if ($user->role === 'driver') {
        $activeVehicleId = $user->vehicleAssignments()
            ->where('is_active', true)
            ->value('vehicle_id');

        return $activeVehicleId === $vehicleId;
    }

    // العميل يستطيع تتبع مركبته فقط إذا عنده شحنة نشطة عليها
    if ($user->role === 'customer') {
        return Shipment::where('customer_id', $user->id)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->exists();
    }

    return false;
});

Broadcast::channel('fleet', function ($user) {
    // قناة الأسطول مخصصة حصرياً للأدمن والموزع
    return in_array($user->role, ['admin', 'dispatcher'], true);
});
Broadcast::channel('user.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});