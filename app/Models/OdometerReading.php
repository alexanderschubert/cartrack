<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property int $odometer_km
 * @property \Illuminate\Support\Carbon\CarbonImmutable $recorded_at
 * @property string $source
 * @property string|null $idempotency_key
 * @property string|null $note
 */
#[Fillable(['vehicle_id', 'recorded_at', 'odometer_km', 'source', 'idempotency_key', 'note'])]
class OdometerReading extends Model
{
    protected function casts(): array
    {
        return ['recorded_at' => 'immutable_datetime', 'odometer_km' => 'integer'];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
