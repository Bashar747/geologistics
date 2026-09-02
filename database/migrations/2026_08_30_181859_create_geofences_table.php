<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->geometry('area_polygon', subtype: 'POLYGON', srid: 4326);
            $table->enum('type', ['warehouse', 'restricted_zone', 'delivery_area']);
            $table->timestamps();
        });

        DB::statement('CREATE INDEX geofences_area_gist ON geofences USING GIST (area_polygon)');
    }

    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};