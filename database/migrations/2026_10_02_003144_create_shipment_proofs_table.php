<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_proofs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('recipient_name');

            $table->string('photo_path')->nullable();

            $table->text('signature')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamp('delivered_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_proofs');
    }
};