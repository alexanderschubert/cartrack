<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_only_see_their_own_vehicles(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $vehicle = Vehicle::create(['user_id' => $owner->id, 'name' => 'Alltagsauto', 'make' => 'Volkswagen', 'model' => 'Polo']);
        $token = $other->createToken('shortcut', ['odometer:read'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/vehicles/'.$vehicle->id.'/odometer')
            ->assertNotFound();
    }

    public function test_odometer_api_is_idempotent_and_rejects_a_newer_lower_reading(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::create(['user_id' => $user->id, 'name' => 'Polo', 'make' => 'Volkswagen', 'model' => 'Polo']);
        $token = $user->createToken('ios-shortcut', ['odometer:read', 'odometer:write'])->plainTextToken;
        $payload = [
            'odometer' => 87452,
            'recorded_at' => '2026-10-05T08:30:00+02:00',
            'source' => 'ios_shortcut',
        ];

        $this->withToken($token)->withHeader('Idempotency-Key', 'shortcut-run-123')
            ->postJson('/api/v1/vehicles/'.$vehicle->id.'/odometer', $payload)
            ->assertCreated()
            ->assertJsonPath('data.odometer', 87452);

        $this->withToken($token)->withHeader('Idempotency-Key', 'shortcut-run-123')
            ->postJson('/api/v1/vehicles/'.$vehicle->id.'/odometer', $payload)
            ->assertOk()
            ->assertJsonPath('data.odometer', 87452);

        $this->assertDatabaseCount('odometer_readings', 1);

        $this->withToken($token)->withHeader('Idempotency-Key', 'shortcut-run-124')
            ->postJson('/api/v1/vehicles/'.$vehicle->id.'/odometer', [
                ...$payload,
                'odometer' => 87000,
                'recorded_at' => '2026-10-05T09:00:00+02:00',
            ])
            ->assertUnprocessable();
    }
}
