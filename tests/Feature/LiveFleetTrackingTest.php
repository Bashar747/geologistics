<?php

namespace Tests\Feature;

use App\Events\VehicleLocationUpdated;
use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use App\Models\User;
use App\Models\Vehicle;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class LiveFleetTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_fleet_channel_allows_only_admin_and_dispatcher(): void
    {
        $admin = User::factory()->admin()->create();
        $dispatcher = User::factory()->dispatcher()->create();
        $driver = User::factory()->driver()->create();
        $customer = User::factory()->create();

        $channels = Broadcast::getChannels();

        $this->assertArrayHasKey('fleet', $channels);

        $authorization = $channels['fleet'];

        $this->assertTrue($authorization($admin));
        $this->assertTrue($authorization($dispatcher));

        $this->assertFalse($authorization($driver));
        $this->assertFalse($authorization($customer));
    }

    public function test_vehicle_location_updated_broadcast_payload(): void
    {
        $vehicle = Vehicle::create([
            'plate_number' => 'TEST-1234',
            'model' => 'Toyota Hilux',
            'type' => 'Pickup',
            'status' => 'in_transit',
            'last_location' => Point::make(32.0, 35.5, srid: 4326),
        ]);

        $event = new VehicleLocationUpdated($vehicle);

        $payload = $event->broadcastWith();

        $this->assertEquals($vehicle->id, $payload['vehicle_id']);
        $this->assertEquals('TEST-1234', $payload['plate_number']);
        $this->assertEquals(35.5, $payload['latitude']);
        $this->assertEquals(32.0, $payload['longitude']);
        $this->assertEquals('in_transit', $payload['status']);

        $this->assertArrayHasKey('driver_name', $payload);
        $this->assertArrayHasKey('current_shipment_number', $payload);
        $this->assertArrayHasKey('speed', $payload);
        $this->assertArrayHasKey('updated_at', $payload);
    }

    public function test_vehicle_location_updated_broadcasts_to_fleet_and_vehicle_channels(): void
    {
        $vehicle = Vehicle::create([
            'plate_number' => 'TEST-1234',
            'model' => 'Toyota Hilux',
            'type' => 'Pickup',
            'status' => 'in_transit',
            'last_location' => Point::make(32.0, 35.5, srid: 4326),
        ]);

        $event = new VehicleLocationUpdated($vehicle);

        $channels = $event->broadcastOn();

        $channelNames = array_map(
            fn ($channel) => $channel->name,
            $channels
        );

        $this->assertContains('private-fleet', $channelNames);
        $this->assertContains(
            'private-vehicle.' . $vehicle->id,
            $channelNames
        );
    }

    public function test_admin_can_assign_vehicle_to_pending_shipment(): void
    {
        $admin = User::factory()->admin()->create();

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $vehicle = Vehicle::create([
            'plate_number' => 'TEST-ASSIGN',
            'model' => 'Mercedes Sprinter',
            'type' => 'van',
            'status' => 'idle',
        ]);

        $shipment = Shipment::create([
            'tracking_number' => 'GL-TESTASSIGN',
            'customer_id' => $customer->id,
           'pickup_location' => Point::makeGeodetic(30.0, 31.0),
'dropoff_location' => Point::makeGeodetic(30.5, 31.5),
            'status' => 'pending',
        ]);

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status' => 'pending',
            'changed_by' => $customer->id,
            'note' => 'Shipment created',
        ]);

        $response = $this->actingAs($admin)
    ->postJson("/api/shipments/{$shipment->id}/assign", [
        'vehicle_id' => $vehicle->id,
    ]);

$response
    ->assertOk()
    ->assertJsonPath('shipment.id', $shipment->id)
    ->assertJsonPath('shipment.status', 'assigned')
    ->assertJsonPath('shipment.vehicle.id', $vehicle->id);
    
$this->assertDatabaseHas('shipments', [
    'id' => $shipment->id,
    'vehicle_id' => $vehicle->id,
    'status' => 'assigned',
]);

$this->assertDatabaseHas('shipment_status_histories', [
    'shipment_id' => $shipment->id,
    'status' => 'assigned',
    'changed_by' => $admin->id,
]);

        $this->assertDatabaseHas('shipment_status_histories', [
            'shipment_id' => $shipment->id,
            'status' => 'assigned',
            'changed_by' => $admin->id,
        ]);
    }
}