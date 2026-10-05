<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('recorded_at');
            $table->unsignedBigInteger('odometer_km')->nullable();
            $table->string('station')->nullable();
            $table->string('place')->nullable();
            $table->decimal('liters', 9, 3);
            $table->decimal('price_per_liter', 9, 4);
            $table->decimal('total_price', 11, 2);
            $table->string('fuel_type', 32)->nullable();
            $table->boolean('full_tank')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['vehicle_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_entries');
    }
};
