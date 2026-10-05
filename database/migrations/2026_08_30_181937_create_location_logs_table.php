<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->geography('location', subtype: 'POINT', srid: 4326);
            $table->decimal('speed', 5, 2)->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX location_logs_location_gist ON location_logs USING GIST (location)');
        }
        DB::statement('CREATE INDEX location_logs_vehicle_time ON location_logs (vehicle_id, recorded_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('location_logs');
    }
};