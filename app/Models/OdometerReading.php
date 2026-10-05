<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'recorded_at', 'odometer_km', 'source', 'idempotency_key', 'note'])]
class OdometerReading extends Model
{
    protected function casts(): array
    {
        return ['recorded_at' => 'immutable_datetime', 'odometer_km' => 'integer'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
