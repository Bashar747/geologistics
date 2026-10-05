<?php

namespace App\Events;

use App\Models\Geofence;
use App\Models\Vehicle;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GeofenceExited implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Vehicle $vehicle, public Geofence $geofence)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dispatchers'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'geofence.exited';
    }

    public function broadcastWith(): array
    {
        return [
            'vehicle_id' => $this->vehicle->id,
            'geofence_id' => $this->geofence->id,
            'geofence_name' => $this->geofence->name,
            'geofence_type' => $this->geofence->type,
            'event_type' => 'exited',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
