<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property \Carbon\CarbonImmutable $recorded_at
 * @property int|null $odometer_km
 * @property string|null $station
 * @property string|null $place
 * @property numeric-string $liters
 * @property numeric-string $price_per_liter
 * @property numeric-string $total_price
 * @property bool $full_tank
 */
#[Fillable(['vehicle_id', 'recorded_at', 'odometer_km', 'station', 'place', 'liters', 'price_per_liter', 'total_price', 'fuel_type', 'full_tank', 'note'])]
class FuelEntry extends Model
{
    protected function casts(): array
    {
        return [
            'recorded_at' => 'immutable_datetime',
            'odometer_km' => 'integer',
            'liters' => 'decimal:3',
            'price_per_liter' => 'decimal:4',
            'total_price' => 'decimal:2',
            'full_tank' => 'boolean',
        ];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
