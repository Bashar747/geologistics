<?php

namespace App\Events;

use App\Models\Vehicle;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Vehicle $vehicle)
    {
    }

    // القناة اللي رح يوصل عليها البث - قناة عامة خاصة بكل مركبة على حدة
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vehicle.' . $this->vehicle->id),
            new PrivateChannel('fleet'),
        ];
    }
    // اسم الحدث اللي رح يوصل للـ frontend (بدل الاسم الطويل الافتراضي)
    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    // البيانات اللي فعلياً رح تنبث - نتحكم فيها يدوياً حتى ما نسرب بيانات زيادة
    public function broadcastWith(): array
    {
        $lat = $this->vehicle->last_location?->getLatitude();
        $lng = $this->vehicle->last_location?->getLongitude();
        $driverName = $this->vehicle->currentAssignment?->driver?->name;
        $currentShipmentNumber = $this->vehicle->shipments()
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->value('tracking_number');

        return [
            'vehicle_id' => $this->vehicle->id,
            'plate_number' => $this->vehicle->plate_number,
            'driver_name' => $driverName,
            'latitude' => $lat,
            'longitude' => $lng,
            'speed' => $this->vehicle->speed ?? 0,
            'status' => $this->vehicle->status,
            'current_shipment_number' => $currentShipmentNumber,
            'updated_at' => $this->vehicle->updated_at,
            'location' => $this->vehicle->last_location,
        ];
    }
}