<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use App\Models\Vehicle;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_access_dashboard(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get('/dashboard');

        $response->assertSuccessful();
        $response->assertSee('Customer Dashboard');
    }

    public function test_customer_sees_only_own_shipments(): void
    {
        $customer1 = User::factory()->create(['role' => 'customer']);
        $customer2 = User::factory()->create(['role' => 'customer']);

        $shipment1 = Shipment::create([
            'tracking_number' => 'GL-CUST-001',
            'customer_id' => $customer1->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'pending',
        ]);

        $shipment2 = Shipment::create([
            'tracking_number' => 'GL-CUST-002',
            'customer_id' => $customer2->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'pending',
        ]);

        Sanctum::actingAs($customer1);

        $response = $this->getJson('/api/shipments');

        $response->assertSuccessful();
        $response->assertJsonFragment(['tracking_number' => 'GL-CUST-001']);
        $response->assertJsonMissing(['tracking_number' => 'GL-CUST-002']);
    }

    public function test_customer_cannot_view_another_customers_shipment(): void
    {
        $customer1 = User::factory()->create(['role' => 'customer']);
        $customer2 = User::factory()->create(['role' => 'customer']);

        $shipment2 = Shipment::create([
            'tracking_number' => 'GL-CUST-002',
            'customer_id' => $customer2->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'pending',
        ]);

        Sanctum::actingAs($customer1);

        $response = $this->getJson('/api/shipments/' . $shipment2->id);

        $response->assertForbidden();
    }

    public function test_customer_cannot_update_another_customers_shipment_or_status(): void
    {
        $customer1 = User::factory()->create(['role' => 'customer']);
        $customer2 = User::factory()->create(['role' => 'customer']);

        $shipment2 = Shipment::create([
            'tracking_number' => 'GL-CUST-002',
            'customer_id' => $customer2->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'pending',
        ]);

        Sanctum::actingAs($customer1);

        // Try to update customer 2's shipment status
        $response = $this->putJson('/api/shipments/' . $shipment2->id, [
            'status' => 'assigned',
        ]);

        $response->assertForbidden();
    }

    public function test_customer_cannot_update_shipment_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $shipment = Shipment::create([
            'tracking_number' => 'GL-CUST-001',
            'customer_id' => $customer->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'pending',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->putJson('/api/shipments/' . $shipment->id, [
            'status' => 'assigned',
        ]);

        $response->assertForbidden();
    }

    public function test_customer_can_view_own_shipment_details(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $shipment = Shipment::create([
            'tracking_number' => 'GL-CUST-001',
            'customer_id' => $customer->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'pending',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/shipments/' . $shipment->id);

        $response->assertSuccessful();
        $response->assertJsonPath('data.tracking_number', 'GL-CUST-001');
    }

    public function test_customer_can_access_appropriate_tracking_information(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vehicle = Vehicle::create(['plate_number' => 'V-999', 'status' => 'in_transit']);

        $shipment = Shipment::create([
            'tracking_number' => 'GL-CUST-001',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'pickup_location' => Point::makeGeodetic(30.0, 31.0),
            'dropoff_location' => Point::makeGeodetic(31.0, 32.0),
            'status' => 'assigned',
        ]);

        $channelCallback = \Illuminate\Support\Facades\Broadcast::getChannels()['vehicle.{vehicleId}'];

        $this->assertTrue($channelCallback($customer, $vehicle->id));
    }

    public function test_customer_cannot_access_unauthorized_vehicle_channel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vehicle = Vehicle::create(['plate_number' => 'V-999', 'status' => 'in_transit']);

        // Customer has no shipment on this vehicle
        $channelCallback = \Illuminate\Support\Facades\Broadcast::getChannels()['vehicle.{vehicleId}'];

        $this->assertFalse($channelCallback($customer, $vehicle->id));
    }

    public function test_customer_cannot_update_vehicle_location(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vehicle = Vehicle::create(['plate_number' => 'V-999', 'status' => 'in_transit']);

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/vehicles/' . $vehicle->id . '/location', [
            'lat' => 24.7136,
            'lng' => 46.6753,
        ]);

        $response->assertForbidden();
    }
}
