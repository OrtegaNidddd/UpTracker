<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\LatencyLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/services');

        $response->assertStatus(401);
    }

    public function test_user_can_list_only_their_own_services(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $service1 = Service::factory()->create(['user_id' => $user1->id, 'name' => 'API Alpha']);
        $service2 = Service::factory()->create(['user_id' => $user2->id, 'name' => 'API Beta']);

        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/services');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'API Alpha'])
            ->assertJsonMissing(['name' => 'API Beta']);
    }

    public function test_user_can_create_service_with_strict_url_validation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Invalid scheme: ftp or javascript
        $invalidResponse = $this->postJson('/api/services', [
            'name' => 'Invalid Scheme Service',
            'url' => 'ftp://example.com/file',
            'interval_seconds' => 60,
        ]);

        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['url']);

        // Valid scheme: https
        $validResponse = $this->postJson('/api/services', [
            'name' => 'Payment Gateway',
            'url' => 'https://api.stripe.com/health',
            'http_method' => 'HEAD',
            'interval_seconds' => 30,
            'latency_threshold_ms' => 500,
        ]);

        $validResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Payment Gateway')
            ->assertJsonPath('data.url', 'https://api.stripe.com/health')
            ->assertJsonPath('data.http_method', 'HEAD')
            ->assertJsonPath('data.interval_seconds', 30)
            ->assertJsonPath('data.latency_threshold_ms', 500);

        $this->assertDatabaseHas('services', [
            'user_id' => $user->id,
            'name' => 'Payment Gateway',
            'http_method' => 'HEAD',
        ]);
    }

    public function test_user_can_view_single_service_with_metrics_and_incidents(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);

        LatencyLog::factory()->create([
            'service_id' => $service->id,
            'latency_ms' => 120,
            'http_status_code' => 200,
            'status' => 'Up',
        ]);

        Incident::factory()->create([
            'service_id' => $service->id,
            'incident_type' => 'Down',
            'started_at' => now()->subMinutes(10),
            'resolved_at' => now()->subMinutes(5),
            'duration_seconds' => 300,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/services/{$service->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $service->id)
            ->assertJsonPath('data.name', $service->name)
            ->assertJsonCount(1, 'data.latest_logs')
            ->assertJsonCount(1, 'data.incidents');
    }

    public function test_user_cannot_view_or_modify_another_users_service(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $serviceOfUser2 = Service::factory()->create(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);

        $showResponse = $this->getJson("/api/services/{$serviceOfUser2->id}");
        $showResponse->assertStatus(403);

        $updateResponse = $this->putJson("/api/services/{$serviceOfUser2->id}", [
            'name' => 'Hacked Name',
        ]);
        $updateResponse->assertStatus(403);

        $deleteResponse = $this->deleteJson("/api/services/{$serviceOfUser2->id}");
        $deleteResponse->assertStatus(403);
    }

    public function test_user_can_update_service(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'interval_seconds' => 60,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/services/{$service->id}", [
            'name' => 'Updated Name',
            'interval_seconds' => 120,
            'latency_threshold_ms' => 1500,
            'http_method' => 'HEAD',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.interval_seconds', 120)
            ->assertJsonPath('data.latency_threshold_ms', 1500)
            ->assertJsonPath('data.http_method', 'HEAD');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Updated Name',
            'interval_seconds' => 120,
        ]);
    }

    public function test_user_can_delete_service(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/services/{$service->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_user_can_query_service_latency_logs_and_incidents(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);

        LatencyLog::factory()->count(3)->create(['service_id' => $service->id]);
        Incident::factory()->count(2)->create(['service_id' => $service->id]);

        Sanctum::actingAs($user);

        $logsResponse = $this->getJson("/api/services/{$service->id}/logs");
        $logsResponse->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $incidentsResponse = $this->getJson("/api/services/{$service->id}/incidents");
        $incidentsResponse->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
