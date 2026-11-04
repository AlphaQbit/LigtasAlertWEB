<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class OAuthRedirectTest extends TestCase
{
    use RefreshDatabase;
    /**
     * URL generation follows the request scheme (http in test, https in prod).
     */
    public function test_oauth_callback_urls_follow_request_scheme(): void
    {
        $apiCallbackUrl = url('/api/auth/microsoft/callback');
        $webCallbackUrl = url('/auth/microsoft/callback');

        $this->assertStringStartsWith('http://', $apiCallbackUrl);
        $this->assertStringStartsWith('http://', $webCallbackUrl);
    }

    /**
     * Behind a trusted proxy (nginx), URLs switch to https.
     */
    public function test_oauth_callback_urls_use_https_behind_trusted_proxy(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PROTO);

        $this->withHeaders(['X-Forwarded-Proto' => 'https'])
             ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1', 'HTTPS' => 'on']);

        $this->get('/up'); // Establish request context
        $this->assertStringStartsWith('https://', url('/api/auth/microsoft/callback'));
        $this->assertStringStartsWith('https://', url('/auth/microsoft/callback'));
    }

    /**
     * Microsoft OAuth start endpoint generates a state and redirect URL.
     * Uses mocked Socialite — no real Microsoft credentials needed.
     */
    public function test_microsoft_start_generates_state_and_redirect_url(): void
    {
        Socialite::shouldReceive('driver->stateless->redirectUrl->redirect')
            ->andReturn(new class {
                public function getTargetUrl(): string
                {
                    return 'https://login.microsoftonline.com/auth';
                }
            });

        $response = $this->getJson('/api/auth/microsoft/start');

        $response->assertOk()
            ->assertJsonStructure(['url', 'state'])
            ->assertJsonPath('url', function (string $url) {
                return str_contains($url, 'state=')
                    && str_contains($url, 'login.microsoftonline.com');
            });
    }

    /**
     * Web Microsoft OAuth redirect uses Socialite scopes.
     * Uses mocked Socialite — no real Microsoft credentials needed.
     */
    public function test_web_microsoft_redirect_generates_redirect_with_scopes(): void
    {
        Socialite::shouldReceive('driver->scopes->redirect')
            ->andReturn(new RedirectResponse('https://login.microsoftonline.com/auth'));

        $response = $this->get('/auth/microsoft/redirect');

        $response->assertRedirect('https://login.microsoftonline.com/auth');
    }

    /**
     * Microsoft OAuth callback rejects missing state.
     * No real Microsoft credentials needed — uses the cache-based state check.
     */
    public function test_microsoft_callback_rejects_missing_state(): void
    {
        $response = $this->getJson('/api/auth/microsoft/callback?code=x');

        $response->assertOk()
            ->assertViewIs('auth.microsoft_done')
            ->assertSee('Sign-in did not complete');
    }

    /**
     * Microsoft OAuth callback rejects unknown state.
     */
    public function test_microsoft_callback_rejects_unknown_state(): void
    {
        $response = $this->getJson('/api/auth/microsoft/callback?state=unknown123&code=x');

        $response->assertOk()
            ->assertViewIs('auth.microsoft_done')
            ->assertSee('Sign-in did not complete');

        // Confirm no token was cached
        $this->assertNull(cache()->get('microsoft_token_unknown123'));
    }

    /**
     * Microsoft OAuth callback rejects empty state.
     */
    public function test_microsoft_callback_rejects_empty_state(): void
    {
        $response = $this->getJson('/api/auth/microsoft/callback?state=&code=x');

        $response->assertOk()
            ->assertViewIs('auth.microsoft_done')
            ->assertSee('Sign-in did not complete');
    }

    /**
     * Full OAuth flow: start → callback (mocked) → finish delivers token.
     */
    public function test_full_microsoft_oauth_flow_delivers_token(): void
    {
        // 1. Start flow
        $state = $this->getJson('/api/auth/microsoft/start')->json('state');

        // 2. Poll finish → pending (404)
        $this->getJson("/api/auth/microsoft/finish?state={$state}")
            ->assertStatus(404)
            ->assertJson(['pending' => true]);

        // 3. Callback with mocked Socialite
        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'ms-id-flow',
            'name' => 'Flow Test',
            'email' => 'flow@example.com',
        ]);
        Socialite::shouldReceive('driver->stateless->redirectUrl->user')
            ->andReturn($socialiteUser);

        $this->getJson("/api/auth/microsoft/callback?state={$state}&code=x")
            ->assertOk();

        // 4. Poll finish → token delivered (200)
        $this->getJson("/api/auth/microsoft/finish?state={$state}")
            ->assertOk()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', 'flow@example.com');
    }

    /**
     * A valid state is accepted exactly once. After a successful callback
     * the state is consumed: a second callback with the same state is
     * rejected and Socialite is not consulted again.
     */
    public function test_microsoft_callback_state_is_consumed_after_successful_use(): void
    {
        $state = $this->getJson('/api/auth/microsoft/start')->json('state');

        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'ms-id-once',
            'name' => 'Once Only',
            'email' => 'once@example.com',
        ]);
        // Exactly one exchange — a replay must not call Socialite again.
        Socialite::shouldReceive('driver->stateless->redirectUrl->user')
            ->once()
            ->andReturn($socialiteUser);

        $this->getJson("/api/auth/microsoft/callback?state={$state}&code=x")
            ->assertOk()
            ->assertSee('Signed in.');

        // State must be gone the moment the exchange succeeds.
        $this->assertNull(cache()->get("microsoft_state_{$state}"));

        // Replay with the same state: rejected, Socialite not consulted.
        $this->getJson("/api/auth/microsoft/callback?state={$state}&code=x")
            ->assertOk()
            ->assertSee('Sign-in did not complete');
    }

    /**
     * A failed code exchange must NOT consume the state, so the user can
     * retry. An unrelated (unknown) state is never touched.
     */
    public function test_microsoft_failed_callback_does_not_consume_state(): void
    {
        $state = $this->getJson('/api/auth/microsoft/start')->json('state');

        Socialite::shouldReceive('driver->stateless->redirectUrl->user')
            ->once()
            ->andThrow(new \RuntimeException('exchange failed'));

        $this->getJson("/api/auth/microsoft/callback?state={$state}&code=x")
            ->assertOk()
            ->assertSee('Microsoft sign-in failed');

        // State survives the failed exchange so a retry is possible.
        $this->assertNotNull(cache()->get("microsoft_state_{$state}"));

        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'ms-id-retry',
            'name' => 'Retry',
            'email' => 'retry@example.com',
        ]);
        Socialite::shouldReceive('driver->stateless->redirectUrl->user')
            ->once()
            ->andReturn($socialiteUser);

        $this->getJson("/api/auth/microsoft/callback?state={$state}&code=x")
            ->assertOk()
            ->assertSee('Signed in.');
    }

    /**
     * Microsoft OAuth callback URL matches the registered redirect URI.
     */
    public function test_callback_url_matches_registered_redirect(): void
    {
        $callbackUrl = url('/api/auth/microsoft/callback');
        $this->assertMatchesRegularExpression(
            '#^https?://[^/]+/api/auth/microsoft/callback$#',
            $callbackUrl
        );
    }
}