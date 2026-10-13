<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_require_authentication(): void
    {
        // JSON endpoint: unauthenticated requests get 401, not a redirect.
        $this->getJson('/admin/reports')->assertUnauthorized();
        $this->getJson('/admin/reports/csv')->assertUnauthorized();
    }

    public function test_reports_summary_counts_only_real_incidents(): void
    {
        $user = User::factory()->create();
        Alert::factory()->count(2)->create(['status' => Alert::STATUS_ACTIVE, 'user_id' => $user->id]);
        Alert::factory()->create(['status' => Alert::STATUS_RESPONDING, 'user_id' => $user->id]);
        Alert::factory()->acknowledged()->create(['user_id' => $user->id]);
        Alert::factory()->resolved()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/admin/reports');

        $response->assertOk()
            ->assertJsonPath('summary.total', 5)
            ->assertJsonPath('summary.active', 2)
            ->assertJsonPath('summary.responding', 1)
            ->assertJsonPath('summary.acknowledged', 1)
            ->assertJsonPath('summary.resolved', 1)
            ->assertJsonPath('summary.open', 4);
    }

    public function test_reports_group_by_facility_and_type(): void
    {
        $user = User::factory()->create();
        Alert::factory()->create(['facility' => 'MCC MAIN', 'type' => 'Lockdown', 'user_id' => $user->id]);
        Alert::factory()->create(['facility' => 'MCC MAIN', 'type' => 'Lockdown', 'user_id' => $user->id]);
        Alert::factory()->create(['facility' => 'Campus', 'type' => 'Fire', 'user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/admin/reports');

        $response->assertOk()
            ->assertJsonPath('by_facility.0.key', 'MCC MAIN')
            ->assertJsonPath('by_facility.0.count', 2)
            ->assertJsonPath('by_type.0.key', 'Lockdown')
            ->assertJsonPath('by_type.0.count', 2);
    }

    public function test_reports_response_times_use_only_acknowledged_incidents(): void
    {
        $user = User::factory()->create();
        Alert::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(10),
            'acknowledged_at' => now()->subMinutes(4),
        ]);
        Alert::factory()->create(['user_id' => $user->id]); // never acknowledged

        $response = $this->actingAs($user)->getJson('/admin/reports');

        $response->assertOk()
            ->assertJsonPath('response_times.count', 1)
            ->assertJsonPath('response_times.min', 360)
            ->assertJsonPath('response_times.max', 360)
            ->assertJsonPath('response_times.avg', 360);
    }

    public function test_reports_responds_to_date_range_and_facility_filters(): void
    {
        $user = User::factory()->create();
        Alert::factory()->create(['facility' => 'MCC MAIN', 'user_id' => $user->id, 'created_at' => now()->subDays(5)]);
        Alert::factory()->create(['facility' => 'Campus', 'user_id' => $user->id, 'created_at' => now()->subDays(1)]);

        $this->actingAs($user)
            ->getJson('/admin/reports?from='.now()->subDays(2)->toDateString())
            ->assertOk()
            ->assertJsonPath('summary.total', 1);

        $this->actingAs($user)
            ->getJson('/admin/reports?facility='.urlencode('MCC MAIN'))
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('by_facility.0.key', 'MCC MAIN');
    }

    public function test_reports_rejects_invalid_date_range(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/admin/reports?from=2099-01-01&to=2000-01-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    public function test_reports_csv_export_contains_real_rows(): void
    {
        $user = User::factory()->create();
        Alert::factory()->create([
            'facility' => 'MCC MAIN',
            'type' => 'Lockdown',
            'location' => 'Room 214',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/reports/csv');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('Room 214')
            ->assertSee('MCC MAIN')
            ->assertSee('Lockdown');
    }
}