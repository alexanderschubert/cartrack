<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class VehicleDataController extends Controller
{
    public function vehicles(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->vehicles()->orderBy('name')->get([
            'id', 'name', 'make', 'model', 'generation', 'year', 'engine', 'fuel_type', 'created_at',
        ])]);
    }

    public function showVehicle(Request $request, int $vehicle): JsonResponse
    {
        $record = $this->ownedVehicle($request, $vehicle);

        return response()->json(['data' => $record->only([
            'id', 'name', 'make', 'model', 'generation', 'year', 'engine', 'fuel_type', 'tank_capacity_liters', 'created_at',
        ])]);
    }

    public function odometer(Request $request, int $vehicle): JsonResponse
    {
        $this->requireAbility($request, 'odometer:read');
        $record = $this->ownedVehicle($request, $vehicle);

        return response()->json(['data' => $record->odometerReadings()->orderByDesc('recorded_at')->orderByDesc('id')->paginate(50)]);
    }

    public function storeOdometer(Request $request, int $vehicle): JsonResponse
    {
        $this->requireAbility($request, 'odometer:write');
        $record = $this->ownedVehicle($request, $vehicle);
        $data = $request->validate([
            'odometer' => ['required', 'integer', 'min:0', 'max:99999999'],
            'recorded_at' => ['required', 'date'],
            'source' => ['required', Rule::in(['manual', 'home_assistant', 'ios_shortcut', 'api', 'import'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $key = $request->header('Idempotency-Key');

        if ($key !== null) {
            validator(['key' => $key], ['key' => ['string', 'max:120']])->validate();
            $existing = $record->odometerReadings()->where('idempotency_key', $key)->first();
            if ($existing) {
                if ($existing->odometer_km !== (int) $data['odometer']) {
                    return response()->json(['message' => 'Idempotency-Key was already used for a different reading.'], 409);
                }

                return response()->json(['data' => $this->readingPayload($existing)], 200);
            }
        }

        $recordedAt = Carbon::parse($data['recorded_at']);
        $latest = $record->odometerReadings()->orderByDesc('recorded_at')->orderByDesc('id')->first();
        if ($latest && $recordedAt->greaterThanOrEqualTo($latest->recorded_at) && $data['odometer'] < $latest->odometer_km) {
            return response()->json(['message' => 'A newer odometer reading cannot be lower than the latest recorded value.', 'errors' => ['odometer' => ['Kilometerstand darf nicht rückwärts laufen.']]], 422);
        }

        $reading = $record->odometerReadings()->create([
            'odometer_km' => $data['odometer'],
            'recorded_at' => $recordedAt,
            'source' => $data['source'],
            'idempotency_key' => $key,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(['data' => $this->readingPayload($reading)], 201);
    }

    public function fuel(Request $request, int $vehicle): JsonResponse
    {
        $this->requireAbility($request, 'fuel:read');
        $record = $this->ownedVehicle($request, $vehicle);

        return response()->json(['data' => $record->fuelEntries()->orderByDesc('recorded_at')->paginate(50)]);
    }

    public function storeFuel(Request $request, int $vehicle): JsonResponse
    {
        $this->requireAbility($request, 'fuel:write');
        $record = $this->ownedVehicle($request, $vehicle);
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
        $data['recorded_at'] = Carbon::parse($data['recorded_at']);
        $data['total_price'] = round((float) $data['liters'] * (float) $data['price_per_liter'], 2);
        $entry = $record->fuelEntries()->create($data);

        return response()->json(['data' => $entry], 201);
    }

    public function insurance(Request $request, int $vehicle): JsonResponse
    {
        $this->requireAbility($request, 'odometer:read');
        $record = $this->ownedVehicle($request, $vehicle);

        return response()->json(['data' => $record->insurancePeriods()->orderByDesc('starts_on')->get()]);
    }

    private function ownedVehicle(Request $request, int $vehicle): Vehicle
    {
        return $request->user()->vehicles()->whereKey($vehicle)->firstOrFail();
    }

    private function requireAbility(Request $request, string $ability): void
    {
        abort_unless($request->user()->currentAccessToken()?->can($ability), 403, 'API token lacks the required ability.');
    }

    private function readingPayload($reading): array
    {
        return [
            'id' => $reading->id,
            'odometer' => $reading->odometer_km,
            'recorded_at' => $reading->recorded_at->toIso8601String(),
            'source' => $reading->source,
            'note' => $reading->note,
        ];
    }
}
