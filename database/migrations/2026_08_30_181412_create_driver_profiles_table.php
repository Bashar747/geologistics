<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('license_number', 50)->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('national_id', 50)->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->enum('status', ['available', 'on_duty', 'suspended'])->default('available');
            $table->date('joined_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};