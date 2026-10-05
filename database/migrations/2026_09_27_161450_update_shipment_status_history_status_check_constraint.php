<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE shipment_status_histories DROP CONSTRAINT IF EXISTS shipment_status_history_status_check');

        DB::statement("
            ALTER TABLE shipment_status_histories
            ADD CONSTRAINT shipment_status_history_status_check
            CHECK (
                status IN (
                    'pending',
                    'assigned',
                    'picked_up',
                    'in_transit',
                    'delivered',
                    'cancelled'
                )
            )
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE shipment_status_histories DROP CONSTRAINT IF EXISTS shipment_status_history_status_check');

        DB::statement("
            ALTER TABLE shipment_status_histories
            ADD CONSTRAINT shipment_status_history_status_check
            CHECK (
                status IN (
                    'pending',
                    'assigned',
                    'picked_up',
                    'delivered',
                    'cancelled'
                )
            )
        ");
    }
};