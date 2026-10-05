<?php

use App\Http\Controllers\Api\V1\VehicleDataController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::get('/vehicles', [VehicleDataController::class, 'vehicles']);
    Route::get('/vehicles/{vehicle}', [VehicleDataController::class, 'showVehicle']);
    Route::get('/vehicles/{vehicle}/odometer', [VehicleDataController::class, 'odometer']);
    Route::post('/vehicles/{vehicle}/odometer', [VehicleDataController::class, 'storeOdometer']);
    Route::get('/vehicles/{vehicle}/fuel', [VehicleDataController::class, 'fuel']);
    Route::post('/vehicles/{vehicle}/fuel', [VehicleDataController::class, 'storeFuel']);
    Route::get('/vehicles/{vehicle}/insurance', [VehicleDataController::class, 'insurance']);

});
