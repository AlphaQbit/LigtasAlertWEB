<?php

namespace Tests\Feature;

use App\Models\Alert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_defaults_to_active_status(): void
    {
        $alert = Alert::factory()->create();

        $this->assertSame(Alert::STATUS_ACTIVE, $alert->status);
        $this->assertNull($alert->acknowledged_at);
        $this->assertNull($alert->resolved_at);
        $this->assertTrue($alert->isOpen());
    }

    public function test_open_scope_excludes_resolved_alerts(): void
    {
        $active = Alert::factory()->create();
        $acknowledged = Alert::factory()->acknowledged()->create();
        $resolved = Alert::factory()->resolved()->create();

        $openIds = Alert::open()->pluck('id');

        $this->assertEqualsCanonicalizing(
            [$active->id, $acknowledged->id],
            $openIds->all(),
        );
    }

    public function test_resolved_alert_is_not_open(): void
    {
        $alert = Alert::factory()->resolved()->create();

        $this->assertFalse($alert->isOpen());
    }

    public function test_response_seconds_is_null_until_acknowledged(): void
    {
        $this->assertNull(Alert::factory()->create()->responseSeconds());
    }

    public function test_response_seconds_measures_time_to_acknowledgement(): void
    {
        $alert = Alert::factory()->create([
            'created_at' => now()->subMinutes(5),
        ]);
        $alert->acknowledged_at = now()->subMinutes(3);

        $this->assertSame(120, $alert->responseSeconds());
    }
}
