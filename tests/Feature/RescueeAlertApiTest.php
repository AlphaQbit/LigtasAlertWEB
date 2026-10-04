<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RescueeAlertApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_rescuee_can_send_an_alert_without_logging_in(): void
    {
        $response = $this->postJson('/api/alerts', [
            'facility' => 'MCC MAIN',
            'type' => 'Medical',
            'location' => 'Room 204',
            'details' => 'Person collapsed near the stairs.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Alert::STATUS_ACTIVE)
            ->assertJsonPath('data.facility', 'MCC MAIN')
            ->assertJsonPath('data.open', true);

        $this->assertDatabaseHas('alerts', [
            'facility' => 'MCC MAIN',
            'type' => 'Medical',
            'location' => 'Room 204',
        ]);
    }

    public function test_details_are_optional(): void
    {
        $this->postJson('/api/alerts', [
            'facility' => 'Campus',
            'type' => 'Fire',
            'location' => 'Court side',
        ])->assertCreated();
    }

    public function test_alert_requires_facility_type_and_location(): void
    {
        $this->postJson('/api/alerts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['facility', 'type', 'location']);
    }

    public function test_rescuee_can_read_back_their_alert(): void
    {
        $id = $this->postJson('/api/alerts', [
            'facility' => 'MCC MADAPDAP',
            'type' => 'Lockdown',
            'location' => 'Gate 2',
        ])->json('data.id');

        $this->withToken(User::factory()->create()->createToken('mobile')->plainTextToken)
            ->getJson("/api/alerts/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id);
    }

    /**
     * Regression: the status feed used to be readable by anyone who guessed an
     * id, leaking a facility and room number for an emergency someone may have
     * already survived.
     */
    public function test_reading_an_alert_requires_a_token(): void
    {
        $id = $this->postJson('/api/alerts', [
            'facility' => 'MCC MAIN',
            'type' => 'Medical',
            'location' => 'Room 204',
        ])->json('data.id');

        $this->getJson("/api/alerts/{$id}")->assertUnauthorized();
    }

    /**
     * A token from an unrelated account still passes the middleware. Alerts have
     * no owner column, so authorization here means "is signed in", not "is this
     * my alert" - flagged so the gap is visible rather than implied.
     */
    public function test_any_signed_in_rescuee_can_read_an_alert_by_id(): void
    {
        $id = $this->postJson('/api/alerts', [
            'facility' => 'Campus',
            'type' => 'Fire',
            'location' => 'Court side',
        ])->json('data.id');

        $this->withToken(User::factory()->create()->createToken('mobile')->plainTextToken)
            ->getJson("/api/alerts/{$id}")
            ->assertOk();
    }

    public function test_missing_alert_returns_404_json(): void
    {
        $this->withToken(User::factory()->create()->createToken('mobile')->plainTextToken)
            ->getJson('/api/alerts/999999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_rescuer_status_change_is_visible_to_the_rescuee_app(): void
    {
        $alert = Alert::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patchJson("/admin/alerts/{$alert->id}/status", [
                'status' => Alert::STATUS_ACKNOWLEDGED,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', Alert::STATUS_ACKNOWLEDGED)
            ->assertJsonPath('data.open', true);

        $this->withToken(User::factory()->create()->createToken('mobile')->plainTextToken)
            ->getJson("/api/alerts/{$alert->id}")
            ->assertOk()
            ->assertJsonPath('data.status', Alert::STATUS_ACKNOWLEDGED);
    }

    public function test_acknowledging_stamps_the_timestamp_once(): void
    {
        $alert = Alert::factory()->create();
        $user = User::factory()->create();

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
        $alert = Alert::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson("/admin/alerts/{$alert->id}/status", ['status' => Alert::STATUS_RESOLVED])
            ->assertOk()
            ->assertJsonPath('data.open', false);

        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_illegal_transition_is_refused(): void
    {
        $alert = Alert::factory()->resolved()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson("/admin/alerts/{$alert->id}/status", ['status' => Alert::STATUS_ACKNOWLEDGED])
            ->assertStatus(409);

        $this->assertSame(Alert::STATUS_RESOLVED, $alert->fresh()->status);
    }

    public function test_dashboard_requires_login(): void
    {
        $this->getJson('/admin/alerts')->assertUnauthorized();
    }

    public function test_dashboard_lists_alerts_with_stats(): void
    {
        Alert::factory()->count(2)->create(['status' => Alert::STATUS_ACTIVE]);
        Alert::factory()->resolved()->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/admin/alerts')
            ->assertOk()
            ->assertJsonPath('stats.active', 2)
            ->assertJsonPath('stats.open', 2)
            ->assertJsonCount(3, 'data');
    }

    public function test_dashboard_can_filter_by_facility(): void
    {
        Alert::factory()->create(['facility' => 'MCC MAIN']);
        Alert::factory()->create(['facility' => 'Campus']);

        $this->actingAs(User::factory()->create())
            ->getJson('/admin/alerts?facility='.urlencode('MCC MAIN'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.facility', 'MCC MAIN');
    }
}
