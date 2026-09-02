<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Clickbar\Magellan\Schema\Macros\Geography;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 50)->unique();
            $table->string('model', 100)->nullable();
            $table->string('type', 100)->nullable();
            $table->enum('status', ['idle', 'in_transit', 'maintenance', 'offline'])
                  ->default('idle');
            $table->geography('last_location', subtype: 'POINT', srid: 4326)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

       
        DB::statement('CREATE INDEX vehicles_last_location_gist ON vehicles USING GIST (last_location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};