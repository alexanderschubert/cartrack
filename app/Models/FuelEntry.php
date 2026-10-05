<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
