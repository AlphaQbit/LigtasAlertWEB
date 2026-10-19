<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }

    public function test_registration_creates_account_but_requires_login(): void
    {
        $response = $this->post('/register', [
            'name' => 'Responder One',
            'email' => 'responder@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Registration successful. Please log in.');

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'responder@example.com']);
    }

    public function test_newly_registered_user_can_log_in_afterwards(): void
    {
        $this->post('/register', [
            'name' => 'Responder One',
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
            'name' => 'Responder One',
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
            'name' => 'Impostor',
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

    public function test_microsoft_redirect_sends_user_to_provider(): void
    {
        $response = $this->get('/auth/microsoft/redirect');

        $response->assertRedirectContains('login.microsoftonline.com');
    }

    public function test_microsoft_callback_creates_user_and_logs_in(): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw([])->map([
            'id' => 'microsoft-id-1',
            'name' => 'Kyla Flores',
            'email' => 'kyla@example.com',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $this->get('/auth/microsoft/callback')->assertRedirect('/admin');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'kyla@example.com',
            'name' => 'Kyla Flores',
        ]);
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
}
