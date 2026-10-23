<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login')->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'id="form-login"'));
        $this->assertSame(1, substr_count($html, 'id="form-register"'));
        $this->assertSame(1, substr_count($html, 'auth/microsoft/redirect'));
        $this->assertStringContainsString('Continue with Microsoft', $html);
        $this->assertStringNotContainsString('Full Name', $html);
    }

    public function test_registration_creates_account_but_requires_login(): void
    {
        $response = $this->post('/register', [
            'email' => 'responder@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Registration successful. Please log in.');

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'responder@example.com',
            'name' => 'responder@example.com',
        ]);
    }

    public function test_newly_registered_user_can_log_in_afterwards(): void
    {
        $this->post('/register', [
            'email' => 'responder@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->post('/login', [
            'email' => 'responder@example.com',
            'password' => 'password123',
        ])->assertRedirect('/admin');

        $this->assertAuthenticated();
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $this->post('/register', [
            'email' => 'responder@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'responder@example.com']);

        $this->post('/register', [
            'email' => 'responder@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'responder@example.com',
            'password' => 'password123',
        ]);

        $this->post('/login', [
            'email' => 'responder@example.com',
            'password' => 'password123',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_login_clears_a_previous_microsoft_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Local Admin',
            'email' => 'local@example.com',
            'password' => 'password123',
        ]);

        $this->withSession([
            'microsoft_profile' => [
                'user_id' => $user->id,
                'name' => 'Old Microsoft Name',
                'email' => 'old@example.com',
                'photo' => 'data:image/jpeg;base64,old-photo',
            ],
        ])->post('/login', [
            'email' => 'local@example.com',
            'password' => 'password123',
        ])->assertRedirect('/admin')
            ->assertSessionMissing('microsoft_profile');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Administrator profile picture for Local Admin')
            ->assertDontSee('old-photo');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'responder@example.com',
            'password' => 'password123',
        ]);

        $this->post('/login', [
            'email' => 'responder@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_admin_header_shows_initials_and_preserves_control_order(): void
    {
        $response = $this->actingAs(User::factory()->create(['name' => 'Alex Johnson']))
            ->get('/admin')
            ->assertOk()
            ->assertSee('AJ')
            ->assertSee('Administrator profile picture for Alex Johnson');

        $html = $response->getContent();
        $facilityPosition = strpos($html, 'class="facility-selector"');
        $avatarPosition = strpos($html, 'class="admin-profile-avatar"');
        $logoutPosition = strpos($html, 'class="btn-logout"');

        $this->assertNotFalse($facilityPosition);
        $this->assertNotFalse($avatarPosition);
        $this->assertNotFalse($logoutPosition);
        $this->assertTrue($facilityPosition < $avatarPosition);
        $this->assertTrue($avatarPosition < $logoutPosition);
    }

    public function test_microsoft_redirect_sends_user_to_provider(): void
    {
        $response = $this->get('/auth/microsoft/redirect');

        $response->assertRedirectContains('login.microsoftonline.com');

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertContains('User.Read', explode(' ', $query['scope'] ?? ''));
    }

    public function test_microsoft_callback_creates_user_and_logs_in(): void
    {
        Http::fake([
            'https://graph.microsoft.com/v1.0/me/photo/$value' => Http::response('profile-photo', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'microsoft-id-1',
            'name' => 'Kyla Flores',
            'email' => 'kyla@example.com',
        ])->setToken('microsoft-access-token');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $this->get('/auth/microsoft/callback')
            ->assertRedirect('/admin')
            ->assertSessionHas('microsoft_profile.photo', 'data:image/jpeg;base64,'.base64_encode('profile-photo'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'kyla@example.com',
            'name' => 'Kyla Flores',
        ]);

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://graph.microsoft.com/v1.0/me/photo/$value'
                && $request->hasHeader('Authorization', 'Bearer microsoft-access-token');
        });

        $this->get('/admin')
            ->assertOk()
            ->assertSee('data:image/jpeg;base64,'.base64_encode('profile-photo'), false)
            ->assertSee('Administrator profile picture for Kyla Flores')
            ->assertSessionMissing('microsoft_profile.token');
    }

    public function test_microsoft_sign_in_succeeds_when_profile_photo_is_unavailable(): void
    {
        Http::fake([
            'https://graph.microsoft.com/v1.0/me/photo/$value' => Http::response('', 404),
        ]);

        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'microsoft-id-2',
            'name' => 'Kyla Flores',
            'email' => 'kyla@example.com',
        ])->setToken('microsoft-access-token');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $this->get('/auth/microsoft/callback')
            ->assertRedirect('/admin')
            ->assertSessionMissing('microsoft_profile.photo');

        $this->assertAuthenticated();
        $this->get('/admin')->assertOk()->assertSee('KF');
    }

    public function test_microsoft_callback_reuses_existing_account_with_same_email(): void
    {
        $existing = User::factory()->create(['email' => 'kyla@example.com']);

        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'microsoft-id-1',
            'name' => 'Kyla Flores',
            'email' => 'kyla@example.com',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $this->get('/auth/microsoft/callback')->assertRedirect('/admin');

        $this->assertAuthenticatedAs($existing);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_microsoft_callback_rejects_account_without_email(): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'microsoft-id-1',
            'name' => 'No Email',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $this->get('/auth/microsoft/callback')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Rate limiting tests for web authentication endpoints.
     */
    public function test_web_login_rate_limit(): void
    {
        User::factory()->create([
            'email' => 'responder@example.com',
            'password' => 'password123',
        ]);

        // First 5 requests should succeed (5 per minute)
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'responder@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        // 6th request should be rate limited
        $this->post('/login', [
            'email' => 'responder@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_web_register_rate_limit(): void
    {
        // First 3 requests should succeed (3 per minute)
        for ($i = 0; $i < 3; $i++) {
            $this->post('/register', [
                'name' => 'Rescuee '.$i,
                'email' => "rescuee{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertRedirect('/login');
        }

        // 4th request should be rate limited
        $this->post('/register', [
            'name' => 'Rescuee 3',
            'email' => 'rescuee3@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(429);
    }

    public function test_web_login_rate_limit_is_per_ip(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        User::factory()->create([
            'email' => 'responder@example.com',
            'password' => 'password123',
        ]);

        // Exhaust rate limit from first IP (10.0.0.1)
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
                ->post('/login', [
                    'email' => 'responder@example.com',
                    'password' => 'wrong-password',
                ])->assertSessionHasErrors('email');
        }

        // 6th request from first IP should be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post('/login', [
                'email' => 'responder@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(429);

        // Request from different IP (10.0.0.2) should NOT be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->post('/login', [
                'email' => 'responder@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
    }
}
