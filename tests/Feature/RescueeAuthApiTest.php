<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class RescueeAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_a_bearer_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Rescuee One',
            'email' => 'rescuee@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', 'rescuee@example.com')
            ->assertJsonMissingPath('user.password');

        $token = $response->json('token');
        $this->assertNotEmpty($token);
    }

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'rescuee@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'rescuee@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_rejects_bad_credentials_with_401(): void
    {
        User::factory()->create([
            'email' => 'rescuee@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'rescuee@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_the_token_owners_profile(): void
    {
        $user = User::factory()->create(['email' => 'rescuee@example.com']);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'rescuee@example.com');
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        // Sanctum stores sha256 hashes, so assert the row is really gone.
        // (A follow-up in-process request would pass via a warmed auth guard
        // in the test harness; live, the token is rejected - verified against
        // a running server.)
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', $token),
        ]);
    }

    public function test_microsoft_start_returns_an_authorize_url_and_state(): void
    {
        $response = $this->getJson('/api/auth/microsoft/start');

        $response->assertOk()
            ->assertJsonStructure(['url', 'state']);

        $url = $response->json('url');

        // Must be a bare URL. Appending to the RedirectResponse object instead
        // of its target stringified the whole 302, headers and all, and the
        // old "contains login.microsoftonline.com" assertion happily passed.
        $this->assertStringStartsWith('https://login.microsoftonline.com/', $url);
        $this->assertStringContainsString('state='.$response->json('state'), $url);
        $this->assertStringNotContainsString('HTTP/1.', $url);
        $this->assertStringNotContainsString('Cache-Control', $url);
    }

    public function test_microsoft_callback_stores_a_token_and_finish_delivers_it(): void
    {
        // Get a real state first: mocking Socialite up front would break the
        // authorize-URL build in microsoftStart(), which needs the real provider.
        $start = $this->getJson('/api/auth/microsoft/start');
        $state = $start->json('state');

        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'microsoft-id-9',
            'name' => 'Kyla Flores',
            'email' => 'kyla@example.com',
        ]);
        // The callback now sets redirectUrl too, so authorize and the token
        // exchange agree. That call is part of the mocked chain.
        Socialite::shouldReceive('driver->stateless->redirectUrl->user')
            ->andReturn($socialiteUser);

        // The browser lands on the callback with the code + state.
        $this->getJson("/api/auth/microsoft/callback?state={$state}&code=the-code")
            ->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'kyla@example.com']);

        $finish = $this->getJson("/api/auth/microsoft/finish?state={$state}");
        $finish->assertOk()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', 'kyla@example.com');

        // The token it handed over really authenticates.
        $this->withToken($finish->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'kyla@example.com');
    }

    public function test_microsoft_start_and_callback_agree_on_the_redirect_uri(): void
    {
        // Regression: microsoftStart asked Microsoft to authorize against
        // /api/auth/microsoft/callback, but the callback's Socialite call fell
        // back to config('services.microsoft.redirect'), the web dashboard's
        // /auth/microsoft/callback. The mismatch only surfaced at the token
        // exchange as AADSTS500112 invalid_client.
        $start = $this->getJson('/api/auth/microsoft/start');
        $state = $start->json('state');

        $this->assertStringContainsString(
            'redirect_uri='.urlencode(url('/api/auth/microsoft/callback')),
            $start->json('url')
        );

        // The authorize URL must not point at the web dashboard's callback.
        $this->assertStringNotContainsString(
            urlencode(url('/auth/microsoft/callback')),
            $start->json('url')
        );

        // And the callback must not silently fall back to that web redirect.
        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'microsoft-id-10',
            'name' => 'Kyla Flores',
            'email' => 'kyla-redirect@example.com',
        ]);

        Socialite::shouldReceive('driver->stateless->redirectUrl')
            ->once()
            ->with(url('/api/auth/microsoft/callback'))
            ->andReturnSelf();
        Socialite::shouldReceive('driver->stateless->redirectUrl->user')
            ->andReturn($socialiteUser);

        $this->getJson("/api/auth/microsoft/callback?state={$state}&code=the-code")
            ->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'kyla-redirect@example.com']);
    }

    public function test_microsoft_finish_is_pending_until_the_callback_runs(): void
    {
        $this->getJson('/api/auth/microsoft/finish?state=nope')
            ->assertStatus(404)
            ->assertJson(['pending' => true]);
    }

    public function test_microsoft_callback_with_unknown_state_shows_failure_page(): void
    {
        $this->getJson('/api/auth/microsoft/callback?state=unknown&code=the-code')
            ->assertOk();

        $this->assertDatabaseMissing('users');
    }
}
