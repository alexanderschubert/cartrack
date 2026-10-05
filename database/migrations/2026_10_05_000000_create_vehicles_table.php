<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('make')->default('Volkswagen');
            $table->string('model')->default('Polo');
            $table->string('generation')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('engine')->nullable();
            $table->string('fuel_type')->nullable();
            $table->decimal('tank_capacity_liters', 7, 2)->nullable();
            $table->text('license_plate')->nullable();
            $table->text('vin')->nullable();
            $table->date('started_using_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
