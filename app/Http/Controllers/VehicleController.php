<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'make' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:100'],
            'generation' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'between:1886,'.(now()->year + 1)],
            'engine' => ['nullable', 'string', 'max:80'],
            'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'hybrid', 'electric', 'other'])],
            'tank_capacity_liters' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'license_plate' => ['nullable', 'string', 'max:32'],
            'vin' => ['nullable', 'string', 'size:17'],
            'started_using_at' => ['nullable', 'date'],
        ]);

        $vehicle = $request->user()->vehicles()->create($data);

        return to_route('dashboard', ['vehicle' => $vehicle->id])->with('success', 'Fahrzeug wurde angelegt.');
    }

    public function storeOdometer(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($request->user()->vehicles()->whereKey($vehicle->id)->exists(), 404);
        $data = $request->validate([
            'odometer_km' => ['required', 'integer', 'min:0', 'max:99999999'],
            'recorded_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $latest = $vehicle->odometerReadings()->orderByDesc('recorded_at')->orderByDesc('id')->first();
        $recordedAt = \Illuminate\Support\Carbon::parse($data['recorded_at']);

        if ($latest && $recordedAt->greaterThanOrEqualTo($latest->recorded_at) && $data['odometer_km'] < $latest->odometer_km) {
            return back()->withErrors(['odometer_km' => 'Ein neuerer Kilometerstand darf nicht niedriger sein.'])->withInput();
        }

        $vehicle->odometerReadings()->create([
            'odometer_km' => $data['odometer_km'],
            'recorded_at' => $recordedAt,
            'source' => 'manual',
            'note' => $data['note'] ?? null,
        ]);

        return to_route('dashboard', ['vehicle' => $vehicle->id])->with('success', 'Kilometerstand wurde gespeichert.');
    }

    public function storeFuel(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($request->user()->vehicles()->whereKey($vehicle->id)->exists(), 404);
        $data = $request->validate([
            'recorded_at' => ['required', 'date'],
            'odometer_km' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'station' => ['nullable', 'string', 'max:120'],
            'place' => ['nullable', 'string', 'max:120'],
            'liters' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'price_per_liter' => ['required', 'numeric', 'gt:0', 'max:100'],
            'fuel_type' => ['nullable', 'string', 'max:32'],
            'full_tank' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['recorded_at'] = \Illuminate\Support\Carbon::parse($data['recorded_at']);
        if ($data['odometer_km'] !== null) {
            $latest = $vehicle->odometerReadings()->orderByDesc('recorded_at')->orderByDesc('id')->first();
            if ($latest && $data['recorded_at']->greaterThanOrEqualTo($latest->recorded_at) && $data['odometer_km'] < $latest->odometer_km) {
                return back()->withErrors(['odometer_km' => 'Ein neuerer Kilometerstand darf nicht niedriger sein.'])->withInput();
            }
        }
        $data['total_price'] = round((float) $data['liters'] * (float) $data['price_per_liter'], 2);
        $vehicle->fuelEntries()->create($data);

        if ($data['odometer_km'] !== null) {
            $vehicle->odometerReadings()->create([
                'recorded_at' => $data['recorded_at'],
                'odometer_km' => $data['odometer_km'],
                'source' => 'manual',
            ]);
        }

        return to_route('dashboard', ['vehicle' => $vehicle->id])->with('success', 'Tankung wurde gespeichert.');
    }

    public function storeInsurance(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($request->user()->vehicles()->whereKey($vehicle->id)->exists(), 404);
        $data = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'start_odometer_km' => ['required', 'integer', 'min:0', 'max:99999999'],
            'distance_limit_km' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $vehicle->insurancePeriods()->create($data);

        return to_route('dashboard', ['vehicle' => $vehicle->id])->with('success', 'Versicherungszeitraum wurde gespeichert.');
    }
}
