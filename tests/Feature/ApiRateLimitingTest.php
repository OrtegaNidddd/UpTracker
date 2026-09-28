<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_endpoints_are_rate_limited(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // Rate limit for api.auth is 10 attempts per minute
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', [
                'email' => 'victim@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // The 11th request should be blocked by rate limiter (HTTP 429)
        $response = $this->postJson('/api/login', [
            'email' => 'victim@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }
}
