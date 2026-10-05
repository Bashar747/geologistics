<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_status_check');

        DB::statement("
            ALTER TABLE shipments
            ADD CONSTRAINT shipments_status_check
            CHECK (status IN (
                'pending',
                'assigned',
                'picked_up',
                'in_transit',
                'delivered',
                'cancelled'
            ))
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_status_check');

        DB::statement("
            ALTER TABLE shipments
            ADD CONSTRAINT shipments_status_check
            CHECK (status IN (
                'pending',
                'assigned',
                'picked_up',
                'delivered',
                'cancelled'
            ))
        ");
    }
};