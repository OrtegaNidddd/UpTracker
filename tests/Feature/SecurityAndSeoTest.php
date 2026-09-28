<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityAndSeoTest extends TestCase
{
    public function test_security_headers_are_present_in_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_sitemap_endpoint_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<urlset', false);
        $response->assertSee('/status/public', false);
    }

    public function test_api_returns_structured_json_on_404_not_found(): void
    {
        $response = $this->getJson('/api/non-existent-endpoint');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Resource_Not_Found',
        ]);
    }
}
