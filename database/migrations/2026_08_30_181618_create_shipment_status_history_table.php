<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_status_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'delivered', 'cancelled']);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('shipment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_status_history');
    }
};