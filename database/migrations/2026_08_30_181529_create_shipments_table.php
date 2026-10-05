<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number', 100)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->geography('pickup_location', subtype: 'POINT', srid: 4326);
            $table->geography('dropoff_location', subtype: 'POINT', srid: 4326);
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled'])
                  ->default('pending');
            $table->timestamp('estimated_arrival')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX shipments_pickup_gist ON shipments USING GIST (pickup_location)');
            DB::statement('CREATE INDEX shipments_dropoff_gist ON shipments USING GIST (dropoff_location)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};