<?php

namespace Tests\Feature;

use App\Models\DriverVehicleAssignment;
use App\Models\Shipment;
use App\Models\ShipmentProof;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProofOfDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function createPodDriverSetup(
        string $shipmentStatus = 'delivered'
    ): array {
        $driver = User::factory()->create([
            'role' => 'driver',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $vehicle = Vehicle::factory()->create([
            'status' => 'idle',
        ]);

        DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => $shipmentStatus,
        ]);

        return [
            $driver,
            $customer,
            $vehicle,
            $shipment,
        ];
    }

    public function test_assigned_driver_can_save_proof_of_delivery(): void
    {
        Storage::fake('public');

        [
            $driver,
            $customer,
            $vehicle,
            $shipment,
        ] = $this->createPodDriverSetup();

        Livewire::actingAs($driver)
            ->test('pages::shipments.⚡show', [
                'shipment' => $shipment,
            ])
            ->set('recipient_name', 'John Doe')
            ->set(
                'signature',
                'data:image/png;base64,test-signature'
            )
            ->set('delivery_latitude', '52.3702160')
            ->set('delivery_longitude', '4.8951680')
            ->call('saveProof');

        $proof = ShipmentProof::where(
            'shipment_id',
            $shipment->id
        )->first();

        $this->assertNotNull($proof);

        $this->assertEquals(
            $driver->id,
            $proof->recorded_by
        );

        $this->assertEquals(
            'John Doe',
            $proof->recipient_name
        );

        $this->assertEquals(
            'data:image/png;base64,test-signature',
            $proof->signature
        );

        $this->assertEquals(
            52.370216,
            (float) $proof->latitude
        );

        $this->assertEquals(
            4.895168,
            (float) $proof->longitude
        );

        $this->assertNotNull(
            $proof->delivered_at
        );

        $this->assertNull(
            $proof->photo_path
        );
    }

    public function test_proof_cannot_be_saved_before_delivery(): void
    {
        [
            $driver,
            $customer,
            $vehicle,
            $shipment,
        ] = $this->createPodDriverSetup('in_transit');

        Livewire::actingAs($driver)
            ->test('pages::shipments.⚡show', [
                'shipment' => $shipment,
            ])
            ->set('recipient_name', 'John Doe')
            ->set(
                'signature',
                'data:image/png;base64,test-signature'
            )
            ->call('saveProof');

        $this->assertDatabaseMissing(
            'shipment_proofs',
            [
                'shipment_id' => $shipment->id,
            ]
        );
    }

    public function test_admin_cannot_record_proof_of_delivery(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $vehicle = Vehicle::factory()->create([
            'status' => 'idle',
        ]);

        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'delivered',
        ]);

        Livewire::actingAs($admin)
            ->test('pages::shipments.⚡show', [
                'shipment' => $shipment,
            ])
            ->set('recipient_name', 'John Doe')
            ->set(
                'signature',
                'data:image/png;base64,test-signature'
            )
            ->call('saveProof');
    }

    public function test_driver_cannot_record_proof_for_another_vehicle(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $driver = User::factory()->create([
            'role' => 'driver',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $assignedVehicle = Vehicle::factory()->create([
            'status' => 'idle',
        ]);

        $shipmentVehicle = Vehicle::factory()->create([
            'status' => 'idle',
        ]);

        DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'vehicle_id' => $assignedVehicle->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $shipmentVehicle->id,
            'status' => 'delivered',
        ]);

        Livewire::actingAs($driver)
            ->test('pages::shipments.⚡show', [
                'shipment' => $shipment,
            ])
            ->set('recipient_name', 'John Doe')
            ->set(
                'signature',
                'data:image/png;base64,test-signature'
            )
            ->call('saveProof');
    }
}