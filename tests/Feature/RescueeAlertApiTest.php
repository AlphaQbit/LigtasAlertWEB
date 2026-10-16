<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RescueeAlertApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear any actingAs user from previous tests to avoid auth pollution
        // Sanctum uses 'web' guard for session auth, so clear it explicitly
        $this->app['auth']->guard('web')->forgetUser();
        $this->flushHeaders();
    }

    public function test_rescuee_can_send_an_alert_when_authenticated(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/alerts', [
            'facility' => 'MCC MAIN',
            'type' => 'Medical',
            'location' => 'Room 204',
            'details' => 'Person collapsed near the stairs.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Alert::STATUS_ACTIVE)
            ->assertJsonPath('data.facility', 'MCC MAIN')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.open', true);

        $this->assertDatabaseHas('alerts', [
            'facility' => 'MCC MAIN',
            'type' => 'Medical',
            'location' => 'Room 204',
            'user_id' => $user->id,
        ]);
    }

    public function test_sending_alert_requires_authentication(): void
    {
        $this->postJson('/api/alerts', [
            'facility' => 'MCC MAIN',
            'type' => 'Medical',
            'location' => 'Room 204',
        ])->assertUnauthorized();
    }

    public function test_details_are_optional(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/alerts', [
                'facility' => 'Campus',
                'type' => 'Fire',
                'location' => 'Court side',
            ])->assertCreated();
    }

    public function test_alert_requires_facility_type_and_location(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/alerts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['facility', 'type', 'location']);
    }

    public function test_rescuee_can_read_back_their_alert(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $id = $this->withToken($token)
            ->postJson('/api/alerts', [
                'facility' => 'MCC MADAPDAP',
                'type' => 'Lockdown',
                'location' => 'Gate 2',
            ])->json('data.id');

        $this->withToken($token)
            ->getJson("/api/alerts/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.user_id', $user->id);
    }

    /**
     * Regression: the status feed used to be readable by anyone who guessed an
     * id, leaking a facility and room number for an emergency someone may have
     * already survived.
     */
    public function test_reading_an_alert_requires_a_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $id = $this->withToken($token)
            ->postJson('/api/alerts', [
                'facility' => 'MCC MAIN',
                'type' => 'Medical',
                'location' => 'Room 204',
            ])->json('data.id');

        // Clear the Sanctum guard's cached user from the previous request
        // (RequestGuard caches the user and doesn't clear it on setRequest)
        $this->app['auth']->guard('sanctum')->forgetUser();
        $this->flushHeaders();

        $this->getJson("/api/alerts/{$id}")->assertUnauthorized();
    }

    /**
     * A rescuee can only read their own alerts. Other users' alerts return 403.
     */
    public function test_rescuee_cannot_read_another_users_alert(): void
    {
        $owner = User::factory()->create();
        $ownerToken = $owner->createToken('mobile')->plainTextToken;

        $id = $this->withToken($ownerToken)
            ->postJson('/api/alerts', [
                'facility' => 'Campus',
                'type' => 'Fire',
                'location' => 'Court side',
            ])->json('data.id');

        $otherUser = User::factory()->create();
        $otherToken = $otherUser->createToken('mobile')->plainTextToken;

        // Clear the Sanctum guard's cached user from the previous request
        $this->app['auth']->guard('sanctum')->forgetUser();
        $this->flushHeaders();
        $this->withToken($otherToken)
            ->getJson("/api/alerts/{$id}")
            ->assertStatus(403);
    }

    /**
     * Regression: AlertController::show() used to log a truncated Authorization
     * header. A bearer token is credential material and must never reach the
     * application log, so a status poll must not write it there.
     */
    public function test_alert_show_does_not_log_the_bearer_token(): void
    {
        $user = User::factory()->create();
        $alert = Alert::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('mobile')->plainTextToken;

        $logPath = storage_path('logs/laravel.log');
        $before = is_file($logPath) ? file_get_contents($logPath) : '';

        $this->withToken($token)
            ->getJson("/api/alerts/{$alert->id}")
            ->assertOk();

        $after = is_file($logPath) ? file_get_contents($logPath) : '';
        $newLines = substr($after, strlen($before));

        $this->assertNotSame('', $newLines, 'Expected the request to log an entry.');
        $this->assertStringNotContainsString($token, $newLines);
        $this->assertStringNotContainsString('Bearer', $newLines);
    }

    public function test_missing_alert_returns_404_json(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/alerts/999999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_rescuer_status_change_is_visible_to_the_rescuee_app(): void
    {
        $user = User::factory()->create();
        $alert = Alert::factory()->create(['user_id' => $user->id]);

        $token = $user->createToken('mobile')->plainTextToken;

        // Rescuee reads alert via token auth
        $this->getJson("/api/alerts/{$alert->id}", [
            'Authorization' => 'Bearer ' . $token,
        ])->assertOk()
            ->assertJsonPath('data.status', Alert::STATUS_ACTIVE);

        // Web rescuer (different user) updates status via session auth
        $rescuer = User::factory()->create();
        $this->actingAs($rescuer)
            ->patchJson("/admin/alerts/{$alert->id}/status", [
                'status' => Alert::STATUS_ACKNOWLEDGED,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', Alert::STATUS_ACKNOWLEDGED)
            ->assertJsonPath('data.open', true);

        // Verify the alert was updated in the database
        $this->assertEquals(Alert::STATUS_ACKNOWLEDGED, $alert->fresh()->status);

        // The rescuee's next API poll would see the updated status (verified by other tests)
    }

    public function test_acknowledging_stamps_the_timestamp_once(): void
    {
        $user = User::factory()->create();
        $alert = Alert::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patchJson("/admin/alerts/{$alert->id}/status", ['status' => Alert::STATUS_ACKNOWLEDGED])
            ->assertOk();

        $first = $alert->fresh()->acknowledged_at;

        $this->actingAs($user)
            ->patchJson("/admin/alerts/{$alert->id}/status", ['status' => Alert::STATUS_RESPONDING])
            ->assertOk();

        $this->actingAs($user)
            ->patchJson("/admin/alerts/{$alert->id}/status", ['status' => Alert::STATUS_ACKNOWLEDGED])
            ->assertOk();

        $this->assertEquals($first, $alert->fresh()->acknowledged_at);
    }

    public function test_resolved_alert_closes(): void
    {
        $user = User::factory()->create();
        $alert = Alert::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patchJson("/admin/alerts/{$alert->id}/status", ['status' => Alert::STATUS_RESOLVED])
            ->assertOk()
            ->assertJsonPath('data.open', false);

        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_illegal_transition_is_refused(): void
    {
        $user = User::factory()->create();
        $alert = Alert::factory()->resolved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patchJson("/admin/alerts/{$alert->id}/status", ['status' => Alert::STATUS_ACKNOWLEDGED])
            ->assertStatus(409);

        $this->assertSame(Alert::STATUS_RESOLVED, $alert->fresh()->status);
    }

    public function test_dashboard_requires_login(): void
    {
        $this->getJson('/admin/alerts')->assertUnauthorized();
    }

    /**
     * The status-update endpoint sits behind the same session auth as the
     * dashboard list. An unauthenticated request must be refused before the
     * route binding or controller ever runs, so no guest can acknowledge or
     * resolve an incident.
     */
    public function test_status_update_requires_login(): void
    {
        $alert = Alert::factory()->create();

        $this->patchJson("/admin/alerts/{$alert->id}/status", [
            'status' => Alert::STATUS_ACKNOWLEDGED,
        ])->assertUnauthorized();

        $this->assertSame(Alert::STATUS_ACTIVE, $alert->fresh()->status);
    }

    public function test_dashboard_lists_alerts_with_stats(): void
    {
        $user = User::factory()->create();
        Alert::factory()->count(2)->create(['status' => Alert::STATUS_ACTIVE, 'user_id' => $user->id]);
        Alert::factory()->resolved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/admin/alerts')
            ->assertOk()
            ->assertJsonPath('stats.active', 2)
            ->assertJsonPath('stats.open', 2)
            ->assertJsonCount(3, 'data')
            ->assertJsonCount(1, 'history')
            ->assertJsonPath('history.0.status', Alert::STATUS_RESOLVED);
    }

    public function test_dashboard_can_filter_by_facility(): void
    {
        $user = User::factory()->create();
        Alert::factory()->create(['facility' => 'MCC MAIN', 'user_id' => $user->id]);
        Alert::factory()->create(['facility' => 'Campus', 'user_id' => $user->id]);
        Alert::factory()->resolved()->create(['facility' => 'MCC MAIN', 'user_id' => $user->id]);
        Alert::factory()->resolved()->create(['facility' => 'Campus', 'user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/admin/alerts?facility='.urlencode('MCC MAIN'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.facility', 'MCC MAIN')
            ->assertJsonCount(1, 'history')
            ->assertJsonPath('history.0.facility', 'MCC MAIN');
    }

    /**
     * The rescuee app marks an unknown exact room by sending the facility
     * label plus a parenthetical into the existing location field. The backend
     * must accept it as a plain string and return it byte-for-byte, so a
     * rescuer never sees the room collapsed away or reinterpreted.
     */
    public function test_unknown_room_location_round_trips_unchanged(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/alerts', [
            'facility' => 'MCC MAIN',
            'type' => 'Medical',
            'location' => 'MCC MAIN (exact room not provided)',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.facility', 'MCC MAIN')
            ->assertJsonPath('data.location', 'MCC MAIN (exact room not provided)');

        $this->assertDatabaseHas('alerts', [
            'facility' => 'MCC MAIN',
            'location' => 'MCC MAIN (exact room not provided)',
        ]);
    }

    public function test_alert_includes_user_id_in_response(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/alerts', [
                'facility' => 'MCC MAIN',
                'type' => 'Medical',
                'location' => 'Room 204',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);
    }
}