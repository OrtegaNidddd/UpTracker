<?php

namespace Tests\Feature;

use App\Models\LatencyLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_services(): void
    {
        $response = $this->get('/services');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_services_page_with_latency_logs(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'Producción API',
            'url' => 'https://api.example.com',
        ]);

        LatencyLog::factory()->create([
            'service_id' => $service->id,
            'latency_ms' => 150,
            'http_status_code' => 200,
            'status' => 'Up',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/services');

        $response->assertStatus(200);
        $response->assertSee('Producción API');
        $response->assertSee('Total Monitoreados');
    }

    public function test_authenticated_user_can_create_service_via_web(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/services', [
            'name' => 'Gateway Web',
            'url' => 'https://gateway.example.com',
            'check_interval' => 60,
            'latency_threshold_ms' => 800,
            'http_method' => 'GET',
        ]);

        $response->assertRedirect('/services');
        $this->assertDatabaseHas('services', [
            'user_id' => $user->id,
            'name' => 'Gateway Web',
            'http_method' => 'GET',
        ]);
    }

    public function test_authenticated_user_can_delete_service_via_web(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/services/{$service->id}");

        $response->assertRedirect('/services');
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_authenticated_user_can_view_service_detail_show_page(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'Microservicio Detalle',
            'url' => 'https://detail.example.com',
        ]);

        $response = $this->actingAs($user)->get("/services/{$service->id}");

        $response->assertStatus(200);
        $response->assertSee('Microservicio Detalle');
        $response->assertSee('Curva de Latencia de Red');
        $response->assertSee('Registro de Sondeos de Red');
        $response->assertSee('Bitácora de Incidentes');
    }

    public function test_user_cannot_view_another_users_service_detail(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->get("/services/{$service->id}");

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_update_service_via_web(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id, 'name' => 'Original Name']);

        $response = $this->actingAs($user)->put("/services/{$service->id}", [
            'name' => 'Updated Name',
            'url' => 'https://updated.example.com',
            'check_interval' => 300,
            'latency_threshold_ms' => 1200,
            'http_method' => 'HEAD',
        ]);

        $response->assertRedirect('/services');
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Updated Name',
            'http_method' => 'HEAD',
        ]);
    }
}
