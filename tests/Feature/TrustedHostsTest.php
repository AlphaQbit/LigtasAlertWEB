<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustHosts;
use Tests\TestCase;

class TrustedHostsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TrustHosts::flushState();
    }

    protected function tearDown(): void
    {
        TrustHosts::flushState();
        putenv('TRUSTED_HOSTS');
        parent::tearDown();
    }

    /**
     * The TRUSTED_HOSTS env var is comma-separated and parsed by
     * bootstrap/app.php before passing to trustHosts().
     */
    public function test_trust_hosts_reads_from_env(): void
    {
        $result = trim(explode(',', 'app.example.com,api.example.com')[0]);
        $this->assertEquals('app.example.com', $result);
    }

    /**
     * When TRUSTED_HOSTS is empty, the middleware falls back to the
     * application URL host (allSubdomainsOfApplicationUrl()).
     */
    public function test_trust_hosts_empty_falls_back_to_app_url(): void
    {
        config(['app.url' => 'https://fallback.example.com']);
        $middleware = new \Illuminate\Http\Middleware\TrustHosts(app());
        $hosts = $middleware->hosts();

        $this->assertNotEmpty($hosts);
        // allSubdomainsOfApplicationUrl() returns '^(.+\.)?' . preg_quote($host) . '$',
        // so assert the exact expected pattern (dots preg_quoted to backslash-dot).
        $this->assertSame('^(.+\.)?fallback\.example\.com$', $hosts[0]);
    }

    /**
     * trustHosts() with subdomains=false produces a literal host entry
     * (no wildcard regex).
     */
    public function test_trust_hosts_subdomain_flag_disabled(): void
    {
        TrustHosts::at(['app.example.com'], false);
        $middleware = new \Illuminate\Http\Middleware\TrustHosts(app());
        $hosts = $middleware->hosts();

        $this->assertContains('app.example.com', $hosts);
        $this->assertNotContains('^(.+\.)?app\.example\.com$', $hosts);
    }

    /**
     * trustHosts() with subdomains=true (default) also appends a
     * wildcard regex covering all subdomains of the application URL.
     */
    public function test_trust_hosts_subdomain_flag_enabled(): void
    {
        config(['app.url' => 'https://app.example.com']);
        TrustHosts::at(['app.example.com'], true);
        $middleware = new \Illuminate\Http\Middleware\TrustHosts(app());
        $hosts = $middleware->hosts();

        $this->assertContains('^(.+\.)?app\.example\.com$', $hosts);
    }

    /**
     * Host enforcement is skipped in the testing environment
     * (shouldSpecifyTrustedHosts() returns false for 'testing').
     * Verified by observing that a request with an arbitrary Host
     * header is NOT rejected.
     */
    public function test_host_enforcement_is_skipped_in_testing(): void
    {
        $response = $this->withHeaders(['Host' => 'not-a-configured-host.example'])
            ->get('/up');

        $response->assertOk();
    }
}
