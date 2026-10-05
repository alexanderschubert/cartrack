<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::post('vehicles/{vehicle}/odometer', [VehicleController::class, 'storeOdometer'])->name('vehicles.odometer.store');
    Route::post('vehicles/{vehicle}/fuel', [VehicleController::class, 'storeFuel'])->name('vehicles.fuel.store');
    Route::post('vehicles/{vehicle}/insurance', [VehicleController::class, 'storeInsurance'])->name('vehicles.insurance.store');
});

require __DIR__.'/settings.php';
