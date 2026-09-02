<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\DriverProfile;
use App\Models\DriverVehicleAssignment;
use App\Models\Geofence;
use App\Models\LocationLog;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\SentNotification;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentStatusHistory;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. أدمن ثابت (حساب معروف تقدر تسجل دخول فيه دايماً)
        $admin = User::factory()->admin()->create([
            'name' => 'Admin GeoLogistics',
            'email' => 'admin@geologistics.test',
        ]);

        // 2. موزّعين (dispatchers)
        $dispatchers = User::factory()->dispatcher()->count(2)->create();

        // 3. مركبات + سائقين + تخصيصات نشطة
        $vehicles = Vehicle::factory()->count(10)->create();

        $drivers = User::factory()->driver()->count(8)
            ->has(DriverProfile::factory(), 'driverProfile')
            ->create();

        // نخصص كل سائق لمركبة (أول 8 مركبات من أصل 10)
        $drivers->each(function (User $driver, int $index) use ($vehicles) {
            DriverVehicleAssignment::factory()->create([
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicles[$index]->id,
                'is_active' => true,
            ]);
        });

        // 4. عملاء
        $customers = User::factory()->count(20)->create();

        // 5. مناطق جغرافية (مستودعات، مناطق تسليم، مناطق محظورة)
        Geofence::factory()->count(5)->create();

        // 6. سجلات مواقع GPS للمركبات (محاكاة حركة فعلية)
        $vehicles->each(function (Vehicle $vehicle) {
            LocationLog::factory()->count(15)->create([
                'vehicle_id' => $vehicle->id,
            ]);
        });

        // 7. شحنات بحالات مختلفة + عناصرها + تاريخ حالاتها + الدفع والتقييم
        // شحنات معلّقة (pending) - بدون مركبة
        Shipment::factory()->count(10)
            ->has(ShipmentItem::factory()->count(2), 'items')
            ->create([
                'customer_id' => fn () => $customers->random()->id,
            ]);

        // شحنات تم تخصيص مركبة لها
        Shipment::factory()->assigned()->count(8)
            ->has(ShipmentItem::factory()->count(2), 'items')
            ->create([
                'customer_id' => fn () => $customers->random()->id,
            ])
            ->each(function (Shipment $shipment) use ($dispatchers) {
                ShipmentStatusHistory::factory()->create([
                    'shipment_id' => $shipment->id,
                    'status' => 'pending',
                    'changed_by' => $dispatchers->random()->id,
                ]);
                ShipmentStatusHistory::factory()->create([
                    'shipment_id' => $shipment->id,
                    'status' => 'assigned',
                    'changed_by' => $dispatchers->random()->id,
                ]);
            });

        // شحنات تم تسليمها بنجاح (مع دفع وتقييم مكتملين)
        Shipment::factory()->delivered()->count(15)
            ->has(ShipmentItem::factory()->count(2), 'items')
            ->create([
                'customer_id' => fn () => $customers->random()->id,
            ])
            ->each(function (Shipment $shipment) use ($dispatchers) {
                foreach (['pending', 'assigned', 'picked_up', 'delivered'] as $status) {
                    ShipmentStatusHistory::factory()->create([
                        'shipment_id' => $shipment->id,
                        'status' => $status,
                        'changed_by' => $dispatchers->random()->id,
                    ]);
                }

                Payment::factory()->create([
                    'shipment_id' => $shipment->id,
                    'status' => 'paid',
                ]);

                Rating::factory()->create([
                    'shipment_id' => $shipment->id,
                    'rated_by' => $shipment->customer_id,
                ]);
            });

        // شحنات ملغاة
        Shipment::factory()->cancelled()->count(3)
            ->has(ShipmentItem::factory()->count(1), 'items')
            ->create([
                'customer_id' => fn () => $customers->random()->id,
            ]);

        // 8. إشعارات مرسلة للعملاء
        $customers->each(function (User $customer) {
            SentNotification::factory()->count(2)->create([
                'user_id' => $customer->id,
            ]);
        });

        // 9. سجل تدقيق عام
        AuditLog::factory()->count(30)->create();

        $this->command->info('✅ Database seeded successfully with realistic GeoLogistics data!');
    }
}