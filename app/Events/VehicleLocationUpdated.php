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
        new PrivateChannel('vehicle.' . $this->vehicle->id), // بدل new Channel(...)
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
        return [
            'vehicle_id' => $this->vehicle->id,
            'location' => $this->vehicle->last_location,
            'status' => $this->vehicle->status,
            'updated_at' => $this->vehicle->updated_at,
        ];
    }
}