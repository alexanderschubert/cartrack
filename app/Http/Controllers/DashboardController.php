<?php

namespace App\Http\Controllers;

use App\Models\OdometerReading;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $vehicles = $request->user()->vehicles()->orderBy('name')->get();
        $vehicle = $vehicles->firstWhere('id', (int) $request->integer('vehicle')) ?? $vehicles->first();

        if (! $vehicle) {
            return Inertia::render('Dashboard', [
                'vehicles' => [],
                'vehicle' => null,
                'metrics' => null,
            ]);
        }

        return Inertia::render('Dashboard', [
            'vehicles' => $vehicles->map(fn (Vehicle $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'make' => $item->make,
                'model' => $item->model,
            ]),
            'vehicle' => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'generation' => $vehicle->generation,
                'year' => $vehicle->year,
                'engine' => $vehicle->engine,
                'fuel_type' => $vehicle->fuel_type,
            ],
            'metrics' => $this->metricsFor($vehicle),
        ]);
    }

    /** @return array<string, mixed> */
    private function metricsFor(Vehicle $vehicle): array
    {
        $latest = $vehicle->odometerReadings()->orderByDesc('recorded_at')->orderByDesc('id')->first();
        $previous = $latest ? $vehicle->odometerReadings()
            ->where(function ($query) use ($latest) {
                $query->where('recorded_at', '<', $latest->recorded_at)
                    ->orWhere(function ($query) use ($latest) {
                        $query->where('recorded_at', $latest->recorded_at)->where('id', '<', $latest->id);
                    });
            })->orderByDesc('recorded_at')->orderByDesc('id')->first() : null;
        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();
        $insurance = $vehicle->insurancePeriods()
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->orderByDesc('starts_on')
            ->first();
        $yearReadings = $vehicle->odometerReadings()
            ->whereBetween('recorded_at', [$yearStart, $yearEnd])
            ->orderBy('recorded_at')
            ->get(['odometer_km', 'recorded_at']);
        $yearStartReading = $yearReadings->first();
        $drivenThisYear = $latest && $yearStartReading
            ? max(0, $latest->odometer_km - $yearStartReading->odometer_km)
            : 0;
        $lastFuel = $vehicle->fuelEntries()->latest('recorded_at')->first();
        $yearFuel = $vehicle->fuelEntries()->whereYear('recorded_at', now()->year)->get();
        $fuelCost = (float) $yearFuel->sum('total_price');
        $liters = (float) $yearFuel->sum('liters');
        $insuranceDriven = $insurance && $latest
            ? max(0, $latest->odometer_km - $insurance->start_odometer_km)
            : null;
        $limit = $insurance?->distance_limit_km;
        $months = $yearReadings->groupBy(fn (OdometerReading $reading): string => $reading->recorded_at->format('Y-m'));
        $mileageSeries = $months->map(function ($readings, $month) {
            return ['month' => $month, 'odometer_km' => $readings->last()->odometer_km];
        })->values();

        return [
            'odometer_km' => $latest?->odometer_km,
            'odometer_at' => $latest?->recorded_at?->toIso8601String(),
            'today_delta_km' => $latest && $latest->recorded_at->isToday() && $previous
                ? max(0, $latest->odometer_km - $previous->odometer_km)
                : null,
            'driven_this_year_km' => $drivenThisYear,
            'insurance' => $insurance ? [
                'starts_on' => $insurance->starts_on->toDateString(),
                'ends_on' => $insurance->ends_on->toDateString(),
                'start_odometer_km' => $insurance->start_odometer_km,
                'limit_km' => $limit,
                'driven_km' => $insuranceDriven,
                'remaining_km' => max(0, $limit - $insuranceDriven),
                'progress_percent' => $limit > 0 ? min(100, round($insuranceDriven / $limit * 100)) : 0,
            ] : null,
            'last_fuel' => $lastFuel ? [
                'id' => $lastFuel->id,
                'recorded_at' => $lastFuel->recorded_at->toIso8601String(),
                'station' => $lastFuel->station,
                'place' => $lastFuel->place,
                'liters' => (float) $lastFuel->liters,
                'price_per_liter' => (float) $lastFuel->price_per_liter,
                'total_price' => (float) $lastFuel->total_price,
            ] : null,
            'average_consumption_l_per_100km' => $this->averageConsumption($vehicle),
            'fuel_cost_this_year' => round($fuelCost, 2),
            'fuel_liters_this_year' => round($liters, 3),
            'mileage_series' => $mileageSeries,
            'recent_fuel' => $vehicle->fuelEntries()->latest('recorded_at')->limit(6)->get()
                ->map(fn ($entry) => [
                    'id' => $entry->id,
                    'recorded_at' => $entry->recorded_at->toIso8601String(),
                    'station' => $entry->station,
                    'place' => $entry->place,
                    'liters' => (float) $entry->liters,
                    'total_price' => (float) $entry->total_price,
                ]),
        ];
    }

    private function averageConsumption(Vehicle $vehicle): ?float
    {
        $entries = $vehicle->fuelEntries()->whereNotNull('odometer_km')->orderBy('recorded_at')->orderBy('id')->get();
        $fullTanks = $entries->where('full_tank', true)->values();
        $consumptions = [];

        for ($index = 1; $index < $fullTanks->count(); $index++) {
            $previous = $fullTanks[$index - 1];
            $current = $fullTanks[$index];
            $distance = $current->odometer_km - $previous->odometer_km;

            if ($distance > 0) {
                $liters = $entries->filter(fn ($entry) => $entry->recorded_at->greaterThan($previous->recorded_at)
                    && $entry->recorded_at->lessThanOrEqualTo($current->recorded_at))
                    ->sum(fn ($entry) => (float) $entry->liters);
                $consumptions[] = $liters / $distance * 100;
            }
        }

        return count($consumptions) ? round(array_sum($consumptions) / count($consumptions), 2) : null;
    }
}
