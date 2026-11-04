<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear the array cache store between tests to isolate rate limiter state
        app('cache.store')->flush();
    }

    /**
     * OAuth start endpoint is rate-limited at 5 requests/min/IP.
     */
    public function test_microsoft_oauth_start_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/api/auth/microsoft/start')->assertOk();
        }

        $this->getJson('/api/auth/microsoft/start')->assertStatus(429);
    }

    /**
     * Microsoft OAuth callback is rate-limited at 5 requests/min/IP.
     */
    public function test_microsoft_oauth_callback_is_rate_limited(): void
    {
        // Exhaust the limit with start requests (same limiter, same IP bucket)
        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/api/auth/microsoft/start')->assertOk();
        }

        // callback should now be rate limited
        $this->getJson('/api/auth/microsoft/callback?state=unknown&code=x')
            ->assertStatus(429);
    }

    /**
     * Microsoft OAuth finish endpoint is rate-limited at 30 requests/min/user when authenticated.
     */
    public function test_microsoft_oauth_finish_is_rate_limited_by_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Authenticated requests should be limited by user ID
        for ($i = 0; $i < 30; $i++) {
            $this->withToken($token)
                ->getJson('/api/auth/microsoft/finish?state=test')
                ->assertStatus(404); // pending state, not rate limited
        }

        // 31st request should be rate limited
        $this->withToken($token)
            ->getJson('/api/auth/microsoft/finish?state=test')
            ->assertStatus(429);
    }

    /**
     * User1 exhausts their rate limit bucket.
     */
    public function test_oauth_rate_limit_user1_exhausted(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        for ($i = 0; $i < 30; $i++) {
            $this->withToken($token)
                ->getJson('/api/auth/microsoft/finish?state=test')
                ->assertStatus(404);
        }

        // 31st request should be rate limited
        $this->withToken($token)
            ->getJson('/api/auth/microsoft/finish?state=test')
            ->assertStatus(429);
    }

    /**
     * User2 should have an independent rate limit bucket (separate test method
     * ensures the Sanctum auth guard is not cached from user1).
     */
    public function test_oauth_rate_limit_user2_independent(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // user2 should NOT be rate limited on first request
        $this->withToken($token)
            ->getJson('/api/auth/microsoft/finish?state=test')
            ->assertStatus(404);
    }

    /**
     * Login endpoint is rate-limited at 5 requests/min/IP.
     */
    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrong',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ])->assertStatus(429);
    }

    /**
     * Register endpoint is rate-limited at 3 requests/min/IP.
     */
    public function test_register_is_rate_limited(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/register', [
                'name' => 'Test ' . $i,
                'email' => "test{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertCreated();
        }

        $this->postJson('/api/auth/register', [
            'name' => 'Test',
            'email' => 'test3@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(429);
    }

    /**
     * Alert submission is rate-limited at 10 requests/min/user.
     */
    public function test_alert_submission_rate_limit(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        for ($i = 0; $i < 10; $i++) {
            $this->withToken($token)
                ->postJson('/api/alerts', [
                    'facility' => 'Test',
                    'type' => 'Medical',
                    'location' => 'Room ' . $i,
                ])->assertCreated();
        }

        $this->withToken($token)
            ->postJson('/api/alerts', [
                'facility' => 'Test',
                'type' => 'Medical',
                'location' => 'Room 10',
            ])->assertStatus(429);
    }

    /**
     * User1 exhausts their rate limit bucket.
     */
    public function test_alert_rate_limit_user1_exhausted(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        for ($i = 0; $i < 10; $i++) {
            $this->withToken($token)
                ->postJson('/api/alerts', [
                    'facility' => 'Test',
                    'type' => 'Medical',
                    'location' => 'Room ' . $i,
                ])->assertCreated();
        }

        // 11th request should be rate limited
        $this->withToken($token)
            ->postJson('/api/alerts', [
                'facility' => 'Test',
                'type' => 'Medical',
                'location' => 'Room 10',
            ])->assertStatus(429);
    }

    /**
     * User2 should have an independent rate limit bucket (separate test method
     * ensures the Sanctum auth guard is not cached from user1).
     */
    public function test_alert_rate_limit_user2_independent(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // user2 should NOT be rate limited on first request
        $this->withToken($token)
            ->postJson('/api/alerts', [
                'facility' => 'Test',
                'type' => 'Medical',
                'location' => 'Room 1',
            ])->assertCreated();
    }

    /**
     * Status update is rate-limited at 30 requests/min/user.
     *
     * The /admin/alerts/{alert}/status route is session-authenticated
     * (web guard), so we authenticate via actingAs() rather than a
     * Sanctum bearer token.
     */
    public function test_status_update_rate_limit(): void
    {
        $user = User::factory()->create();
        $alert = \App\Models\Alert::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        for ($i = 0; $i < 30; $i++) {
            $this->patchJson("/admin/alerts/{$alert->id}/status", [
                'status' => \App\Models\Alert::STATUS_ACKNOWLEDGED,
            ])->assertOk();

            // Reset alert status for next iteration
            $alert->refresh();
            $alert->update(['status' => \App\Models\Alert::STATUS_ACTIVE]);
        }

        $this->patchJson("/admin/alerts/{$alert->id}/status", [
            'status' => \App\Models\Alert::STATUS_ACKNOWLEDGED,
        ])->assertStatus(429);
    }

    /**
     * Status update rate limit is per user.
     *
     * The /admin/alerts/{alert}/status route is session-authenticated
     * (web guard), so we authenticate via actingAs() rather than a
     * Sanctum bearer token.
     */
    public function test_status_update_rate_limit_is_per_user(): void
    {
        $user1 = User::factory()->create();
        $alert1 = \App\Models\Alert::factory()->create(['user_id' => $user1->id]);

        $user2 = User::factory()->create();
        $alert2 = \App\Models\Alert::factory()->create(['user_id' => $user2->id]);

        // Exhaust user1's limit
        $this->actingAs($user1);
        for ($i = 0; $i < 30; $i++) {
            $this->patchJson("/admin/alerts/{$alert1->id}/status", [
                'status' => \App\Models\Alert::STATUS_ACKNOWLEDGED,
            ])->assertOk();

            $alert1->refresh();
            $alert1->update(['status' => \App\Models\Alert::STATUS_ACTIVE]);
        }

        // user2 should NOT be rate limited
        $this->actingAs($user2);
        $this->patchJson("/admin/alerts/{$alert2->id}/status", [
            'status' => \App\Models\Alert::STATUS_ACKNOWLEDGED,
        ])->assertOk();
    }
}