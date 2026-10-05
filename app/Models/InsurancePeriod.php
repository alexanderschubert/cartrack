<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property \Carbon\CarbonImmutable $starts_on
 * @property \Carbon\CarbonImmutable $ends_on
 * @property int $start_odometer_km
 * @property int $distance_limit_km
 * @property string|null $note
 */
#[Fillable(['vehicle_id', 'starts_on', 'ends_on', 'start_odometer_km', 'distance_limit_km', 'note'])]
class InsurancePeriod extends Model
{
    protected function casts(): array
    {
        return ['starts_on' => 'immutable_date', 'ends_on' => 'immutable_date', 'start_odometer_km' => 'integer', 'distance_limit_km' => 'integer'];
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
