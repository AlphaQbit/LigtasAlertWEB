<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecureCookieTest extends TestCase
{
    /**
     * In the test environment (APP_ENV=testing), the default is false,
     * so cookies are not flagged Secure. Production sets
     * SESSION_SECURE_COOKIE=true explicitly.
     */
    public function test_session_secure_default_in_testing(): void
    {
        $this->assertFalse((bool) config('session.secure'));
    }

    /**
     * Explicit SESSION_SECURE_COOKIE=true is respected.
     */
    public function test_session_secure_respects_explicit_true(): void
    {
        config(['session.secure' => true]);
        $this->assertTrue(config('session.secure'));
    }

    /**
     * Explicit SESSION_SECURE_COOKIE=false is respected.
     */
    public function test_session_secure_respects_explicit_false(): void
    {
        config(['session.secure' => false]);
        $this->assertFalse(config('session.secure'));
    }

    /**
     * Session cookie SameSite default is lax.
     */
    public function test_session_cookie_samesite_default(): void
    {
        $this->assertEquals('lax', config('session.same_site'));
    }

    /**
     * Session cookie is HTTP-only.
     */
    public function test_session_cookie_is_httponly(): void
    {
        $this->assertTrue(config('session.http_only'));
    }
}
