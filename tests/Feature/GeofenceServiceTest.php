<?php

namespace Tests\Feature;

use App\Events\GeofenceEntered;
use App\Models\Geofence;
use App\Models\Shipment;
use App\Models\Vehicle;
use App\Listeners\HandleGeofenceEntered;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Data\Geometries\Polygon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeofenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_geofence_entered_updates_assigned_shipment_to_delivered()
    {
        $vehicle = Vehicle::factory()->create();
        $ring = LineString::make([
            Point::make(36.0, 31.0, srid: 4326),
            Point::make(37.0, 31.0, srid: 4326),
            Point::make(37.0, 32.0, srid: 4326),
            Point::make(36.0, 32.0, srid: 4326),
            Point::make(36.0, 31.0, srid: 4326),
        ], srid: 4326);

        $geofence = Geofence::create([
            'name' => 'Main Warehouse Delivery',
            'type' => 'delivery_area',
            'area_polygon' => Polygon::make([$ring], srid: 4326),
        ]);

        $shipment = Shipment::factory()->assigned()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'assigned',
        ]);

        $event = new GeofenceEntered($vehicle, $geofence);
        $listener = new HandleGeofenceEntered();
        $listener->handle($event);

        $this->assertEquals('delivered', $shipment->fresh()->status);
    }
}
