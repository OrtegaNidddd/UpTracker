<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_via_api_and_receive_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Alice Doe',
            'email' => 'alice@example.com',
            'password' => 'SecurePassword123!',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token_type',
                'access_token',
                'user' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
        ]);
    }

    public function test_user_can_login_via_api_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('ValidPassword123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'bob@example.com',
            'password' => 'ValidPassword123!',
            'device_name' => 'mobile-app',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token_type',
                'access_token',
                'user' => ['id', 'name', 'email'],
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('ValidPassword123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'bob@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_view_profile_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        // View profile
        $profileResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $profileResponse->assertStatus(200)
            ->assertJsonPath('user.email', $user->email);

        // Update profile
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson('/api/user', [
                'name' => 'Bob The Builder',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('user.name', 'Bob The Builder');

        // Logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Reset memory-cached authentication guard to simulate a fresh incoming request
        app('auth')->forgetGuards();

        // Token should now be rejected as unauthenticated
        $revokedResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $revokedResponse->assertStatus(401);
    }
}
