<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $make
 * @property string|null $model
 * @property string|null $generation
 * @property int|null $year
 * @property string|null $engine
 * @property string|null $fuel_type
 * @property int|null $tank_capacity_liters
 */
#[Fillable(['user_id', 'name', 'make', 'model', 'generation', 'year', 'engine', 'fuel_type', 'tank_capacity_liters', 'license_plate', 'vin', 'started_using_at'])]
class Vehicle extends Model
{
    protected function casts(): array
    {
        return ['license_plate' => 'encrypted', 'vin' => 'encrypted'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OdometerReading, $this> */
    public function odometerReadings(): HasMany
    {
        return $this->hasMany(OdometerReading::class);
    }

    /** @return HasMany<FuelEntry, $this> */
    public function fuelEntries(): HasMany
    {
        return $this->hasMany(FuelEntry::class);
    }

    /** @return HasMany<InsurancePeriod, $this> */
    public function insurancePeriods(): HasMany
    {
        return $this->hasMany(InsurancePeriod::class);
    }
}
