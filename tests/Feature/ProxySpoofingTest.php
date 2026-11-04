<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class ProxySpoofingTest extends TestCase
{
    /**
     * Symfony's getClientIp() returns the FIRST entry in X-Forwarded-For
     * (the leftmost, i.e. the original client). When the connection source
     * (REMOTE_ADDR) is NOT a trusted proxy, the forwarded header is ignored
     * entirely and REMOTE_ADDR is used.
     *
     * This is a unit test of Symfony's Request forwarding logic, NOT
     * container-level proof of nginx behavior. Container/nginx verification
     * requires a live Docker environment (not available here per restrictions).
     */
    public function test_spoofed_x_forwarded_for_is_ignored_when_proxy_not_trusted(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR);

        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 5.6.7.8',
        ]);

        // Connection source 10.0.0.1 is NOT a trusted proxy, so the
        // X-Forwarded-For header is ignored entirely.
        $this->assertEquals('10.0.0.1', $request->getClientIp());
    }

    /**
     * When the connection source IS a trusted proxy, the last entry in
     * X-Forwarded-For is used as the client IP (Symfony picks the rightmost
     * trusted entry).
     */
    public function test_trusted_proxy_uses_forwarded_chain_for_client_ip(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR);

        // nginx appends the real client IP (203.0.113.10) to the chain
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
        ]);

        $this->assertEquals('203.0.113.10', $request->getClientIp());
    }

    public function test_spoofed_x_forwarded_proto_is_ignored_when_proxy_not_trusted(): void
    {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_PROTO);

        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $this->assertFalse($request->isSecure());
    }

    public function test_trusted_proxy_respects_x_forwarded_proto(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PROTO);

        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $this->assertTrue($request->isSecure());
    }

    public function test_spoofed_x_forwarded_host_is_ignored_when_proxy_not_trusted(): void
    {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_HOST);

        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED_HOST' => 'evil.com',
        ]);

        $this->assertNotEquals('evil.com', $request->getHost());
    }

    public function test_multiple_spoofed_headers_are_ignored_when_proxy_not_trusted(): void
    {
        Request::setTrustedProxies([], 0);

        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
            'HTTP_X_FORWARDED_HOST' => 'evil.com',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_FORWARDED' => 'for=1.2.3.4;host=evil.com;proto=https',
        ]);

        $this->assertEquals('10.0.0.1', $request->getClientIp());
        $this->assertFalse($request->isSecure());
        $this->assertStringNotContainsString('evil.com', $request->getHttpHost());
    }
}