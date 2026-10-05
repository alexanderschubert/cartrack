<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'make', 'model', 'generation', 'year', 'engine', 'fuel_type', 'tank_capacity_liters', 'license_plate', 'vin', 'started_using_at'])]
class Vehicle extends Model
{
    protected function casts(): array
    {
        return ['license_plate' => 'encrypted', 'vin' => 'encrypted'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function odometerReadings(): HasMany
    {
        return $this->hasMany(OdometerReading::class);
    }

    public function fuelEntries(): HasMany
    {
        return $this->hasMany(FuelEntry::class);
    }

    public function insurancePeriods(): HasMany
    {
        return $this->hasMany(InsurancePeriod::class);
    }
}
