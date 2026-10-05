<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odometer_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('recorded_at');
            $table->unsignedBigInteger('odometer_km');
            $table->string('source', 32)->default('manual');
            $table->string('idempotency_key', 120)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['vehicle_id', 'recorded_at']);
            $table->unique(['vehicle_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odometer_readings');
    }
};
