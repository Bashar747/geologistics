<?php

namespace Tests\Feature;

use App\Models\DriverVehicleAssignment;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vehicle;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;


class DriverWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_view_their_assigned_vehicle()
    {
        $driver = User::factory()->driver()->create(['phone' => '96711111111', 'email' => 'driver1@test.com']);
        $vehicle = Vehicle::create(['plate_number' => 'V-101', 'status' => 'idle']);

        DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($driver);

        $response = $this->getJson('/api/vehicles/' . $vehicle->id);
        $response->assertSuccessful();
        $response->assertJsonPath('data.plate_number', 'V-101');
    }

    public function test_driver_cannot_access_another_drivers_vehicle()
    {
        $driver1 = User::factory()->driver()->create(['phone' => '96711111111', 'email' => 'driver1@test.com']);
        $driver2 = User::factory()->driver()->create(['phone' => '96722222222', 'email' => 'driver2@test.com']);
        $vehicle1 = Vehicle::create(['plate_number' => 'V-101', 'status' => 'idle']);
        $vehicle2 = Vehicle::create(['plate_number' => 'V-202', 'status' => 'idle']);

        DriverVehicleAssignment::create([
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle1->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        DriverVehicleAssignment::create([
            'driver_id' => $driver2->id,
            'vehicle_id' => $vehicle2->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($driver1);

        $response = $this->getJson('/api/vehicles/' . $vehicle2->id);
        $response->assertForbidden();
    }

    public function test_driver_can_see_their_assigned_shipment()
    {
        $driver = User::factory()->driver()->create(['phone' => '96711111111', 'email' => 'driver1@test.com']);
        $vehicle = Vehicle::create(['plate_number' => 'V-101', 'status' => 'idle']);

        DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $shipment = Shipment::create([
            'tracking_number' => 'GL-TEST-001',
            'customer_id' => User::factory()->create()->id,
            'vehicle_id' => $vehicle->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'assigned',
        ]);

        Sanctum::actingAs($driver);

        $response = $this->getJson('/api/shipments/' . $shipment->id);
        $response->assertSuccessful();
        $response->assertJsonPath('data.tracking_number', 'GL-TEST-001');
    }

    public function test_driver_cannot_access_another_shipment()
    {
        $driver = User::factory()->driver()->create(['phone' => '96711111111', 'email' => 'driver1@test.com']);
        $vehicle1 = Vehicle::create(['plate_number' => 'V-101', 'status' => 'idle']);
        $vehicle2 = Vehicle::create(['plate_number' => 'V-202', 'status' => 'idle']);

        DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle1->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $shipment2 = Shipment::create([
            'tracking_number' => 'GL-TEST-002',
            'customer_id' => User::factory()->create()->id,
            'vehicle_id' => $vehicle2->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'assigned',
        ]);

        Sanctum::actingAs($driver);

        $response = $this->getJson('/api/shipments/' . $shipment2->id);
        $response->assertForbidden();
    }

    public function test_valid_status_transition_works_and_creates_history()
    {
        $driver = User::factory()->driver()->create(['phone' => '96711111111', 'email' => 'driver1@test.com']);
        $vehicle = Vehicle::create(['plate_number' => 'V-101', 'status' => 'idle']);

        DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $shipment = Shipment::create([
            'tracking_number' => 'GL-TEST-001',
            'customer_id' => User::factory()->create()->id,
            'vehicle_id' => $vehicle->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'assigned',
        ]);

        Sanctum::actingAs($driver);

        // assigned -> picked_up
        $response = $this->putJson('/api/shipments/' . $shipment->id, ['status' => 'picked_up']);
        $response->assertSuccessful();
        $this->assertEquals('picked_up', $shipment->fresh()->status);

        // picked_up -> in_transit
        $response = $this->putJson('/api/shipments/' . $shipment->id, ['status' => 'in_transit']);
        $response->assertSuccessful();
        $this->assertEquals('in_transit', $shipment->fresh()->status);

        // in_transit -> delivered
        $response = $this->putJson('/api/shipments/' . $shipment->id, ['status' => 'delivered']);
        $response->assertSuccessful();
        $this->assertEquals('delivered', $shipment->fresh()->status);

       $this->assertDatabaseHas('shipment_status_histories', [
            'shipment_id' => $shipment->id,
            'status' => 'delivered',
            'changed_by' => $driver->id,
        ]);
    }

    public function test_invalid_status_transition_is_rejected()
    {
        $driver = User::factory()->driver()->create(['phone' => '96711111111', 'email' => 'driver1@test.com']);
        $vehicle = Vehicle::create(['plate_number' => 'V-101', 'status' => 'idle']);

        DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $shipment = Shipment::create([
            'tracking_number' => 'GL-TEST-001',
            'customer_id' => User::factory()->create()->id,
            'vehicle_id' => $vehicle->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'assigned',
        ]);

        Sanctum::actingAs($driver);

        // Try skipping directly to delivered from assigned
        $response = $this->putJson('/api/shipments/' . $shipment->id, ['status' => 'delivered']);
        $response->assertStatus(422);
        $this->assertEquals('assigned', $shipment->fresh()->status);
    }

    public function test_driver_can_update_their_assigned_vehicle_location()
    {
        $driver = User::factory()->driver()->create(['phone' => '96711111111', 'email' => 'driver1@test.com']);
        $vehicle = Vehicle::create(['plate_number' => 'V-101', 'status' => 'idle']);

        DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/vehicles/' . $vehicle->id . '/location', [
            'lat' => 24.7136,
            'lng' => 46.6753,
            'speed' => 50,
        ]);

        $response->assertSuccessful();
        $this->assertNotNull($vehicle->fresh()->last_location);
    }

    public function test_driver_cannot_update_another_vehicle_location()
    {
        $driver = User::factory()->driver()->create(['phone' => '96711111111', 'email' => 'driver1@test.com']);
        $vehicle1 = Vehicle::create(['plate_number' => 'V-101', 'status' => 'idle']);
        $vehicle2 = Vehicle::create(['plate_number' => 'V-202', 'status' => 'idle']);

        DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle1->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/vehicles/' . $vehicle2->id . '/location', [
            'lat' => 24.7136,
            'lng' => 46.6753,
            'speed' => 50,
        ]);

        $response->assertForbidden();
    }

    public function test_customer_cannot_update_shipment_status(): void
{
    $customer = User::factory()->create([
        'role' => 'customer',
    ]);

    $shipment = Shipment::create([
        'tracking_number' => 'GL-CUSTOMER-UPDATE',
        'customer_id' => $customer->id,
        'pickup_location' => Point::makeGeodetic(30.0, 31.0),
        'dropoff_location' => Point::makeGeodetic(30.5, 31.5),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($customer)
        ->putJson("/api/shipments/{$shipment->id}", [
            'status' => 'cancelled',
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'status' => 'pending',
    ]);
}

public function test_driver_cannot_update_shipment_not_assigned_to_their_vehicle(): void
{
    $driver = User::factory()->driver()->create();

    $vehicle = Vehicle::create([
        'plate_number' => 'TEST-OTHER',
        'model' => 'Mercedes Sprinter',
        'type' => 'van',
        'status' => 'idle',
    ]);

    $shipment = Shipment::create([
        'tracking_number' => 'GL-OTHER-VEHICLE',
        'customer_id' => User::factory()->create([
            'role' => 'customer',
        ])->id,
        'vehicle_id' => $vehicle->id,
        'pickup_location' => Point::makeGeodetic(30.0, 31.0),
        'dropoff_location' => Point::makeGeodetic(30.5, 31.5),
        'status' => 'assigned',
    ]);

    $response = $this->actingAs($driver)
        ->putJson("/api/shipments/{$shipment->id}", [
            'status' => 'picked_up',
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'status' => 'assigned',
    ]);
}
public function test_driver_cannot_skip_a_status_transition(): void
{
    $driver = User::factory()->driver()->create();

    $vehicle = Vehicle::create([
        'plate_number' => 'TEST-SKIP',
        'model' => 'Mercedes Sprinter',
        'type' => 'van',
        'status' => 'idle',
    ]);

    $driver->vehicleAssignments()->create([
        'vehicle_id' => $vehicle->id,
        'is_active' => true,
    ]);

    $customer = User::factory()->create([
        'role' => 'customer',
    ]);

    $shipment = Shipment::create([
        'tracking_number' => 'GL-SKIP-STATUS',
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'pickup_location' => Point::makeGeodetic(30.0, 31.0),
        'dropoff_location' => Point::makeGeodetic(30.5, 31.5),
        'status' => 'assigned',
    ]);

    $response = $this->actingAs($driver)
        ->putJson("/api/shipments/{$shipment->id}", [
            'status' => 'delivered',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'Invalid status transition for driver',
        ]);

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'status' => 'assigned',
    ]);
}
public function test_admin_can_update_shipment_status(): void
{
    $admin = User::factory()->admin()->create();

    $customer = User::factory()->create([
        'role' => 'customer',
    ]);

    $shipment = Shipment::create([
        'tracking_number' => 'GL-ADMIN-STATUS',
        'customer_id' => $customer->id,
        'pickup_location' => Point::makeGeodetic(30.0, 31.0),
        'dropoff_location' => Point::makeGeodetic(30.5, 31.5),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->putJson("/api/shipments/{$shipment->id}", [
            'status' => 'assigned',
            'note' => 'Assigned by admin',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('shipment.id', $shipment->id)
        ->assertJsonPath('shipment.status', 'assigned');

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'status' => 'assigned',
    ]);
}

  public function test_dispatcher_can_update_shipment_status(): void
{
    $dispatcher = User::factory()->dispatcher()->create();

    $customer = User::factory()->create([
        'role' => 'customer',
    ]);

    $shipment = Shipment::create([
        'tracking_number' => 'GL-DISPATCHER-STATUS',
        'customer_id' => $customer->id,
        'pickup_location' => Point::makeGeodetic(30.0, 31.0),
        'dropoff_location' => Point::makeGeodetic(30.5, 31.5),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($dispatcher)
        ->putJson("/api/shipments/{$shipment->id}", [
            'status' => 'assigned',
            'note' => 'Assigned by dispatcher',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('shipment.id', $shipment->id)
        ->assertJsonPath('shipment.status', 'assigned');

    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'status' => 'assigned',
    ]);
}



}
