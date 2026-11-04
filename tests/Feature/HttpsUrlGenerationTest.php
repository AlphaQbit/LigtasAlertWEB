<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Tests\TestCase;

class HttpsUrlGenerationTest extends TestCase
{
    /**
     * URL generation follows the request scheme (http in test, https in prod).
     */
    public function test_url_generation_follows_request_scheme(): void
    {
        $url = url('/test-path');
        $this->assertStringStartsWith('http://', $url);
    }

    /**
     * Behind a trusted proxy (nginx), URLs switch to https.
     */
    public function test_url_generation_switches_to_https_behind_trusted_proxy(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PROTO);

        $this->withHeaders(['X-Forwarded-Proto' => 'https'])
             ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1', 'HTTPS' => 'on']);

        $this->get('/up'); // Establish request context
        $this->assertStringStartsWith('https://', url('/test-path'));
    }
}
