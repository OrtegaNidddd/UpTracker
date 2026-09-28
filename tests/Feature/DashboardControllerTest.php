<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\LatencyLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_multi_tenancy_isolation_only_shows_authenticated_users_services(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $serviceA1 = Service::factory()->create([
            'user_id' => $userA->id,
            'name' => 'Microservicio Alpha Usuario A',
        ]);
        $serviceA2 = Service::factory()->create([
            'user_id' => $userA->id,
            'name' => 'Microservicio Beta Usuario A',
        ]);
        $serviceB = Service::factory()->create([
            'user_id' => $userB->id,
            'name' => 'Servicio Secreto Usuario B',
        ]);

        $response = $this->actingAs($userA)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Microservicio Alpha Usuario A');
        $response->assertSee('Microservicio Beta Usuario A');
        $response->assertDontSee('Servicio Secreto Usuario B');

        $viewServices = $response->viewData('services');
        $this->assertCount(2, $viewServices);
        $this->assertTrue($viewServices->contains('id', $serviceA1->id));
        $this->assertTrue($viewServices->contains('id', $serviceA2->id));
        $this->assertFalse($viewServices->contains('id', $serviceB->id));
    }

    public function test_uptime_24h_calculation_is_accurate(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);

        // 3 logs 'Up' y 1 log 'Down' dentro de las últimas 24 horas (75% disponibilidad)
        LatencyLog::factory()->create([
            'service_id' => $service->id,
            'status' => 'Up',
            'checked_at' => now()->subHours(2),
        ]);
        LatencyLog::factory()->create([
            'service_id' => $service->id,
            'status' => 'Up',
            'checked_at' => now()->subHours(4),
        ]);
        LatencyLog::factory()->create([
            'service_id' => $service->id,
            'status' => 'Up',
            'checked_at' => now()->subHours(8),
        ]);
        LatencyLog::factory()->create([
            'service_id' => $service->id,
            'status' => 'Down',
            'checked_at' => now()->subHours(12),
        ]);

        // 1 log 'Down' antiguo (hace 26 horas) que NO debe influir en el cálculo de 24h
        LatencyLog::factory()->create([
            'service_id' => $service->id,
            'status' => 'Down',
            'checked_at' => now()->subHours(26),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $loadedService = $response->viewData('services')->first();

        $this->assertSame(75.0, $loadedService->uptime_24h);
    }

    public function test_uptime_defaults_to_100_percent_when_no_logs_exist(): void
    {
        $user = User::factory()->create();
        Service::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $loadedService = $response->viewData('services')->first();

        $this->assertSame(100.0, $loadedService->uptime_24h);
    }

    public function test_latency_history_retrieves_last_50_logs_ordered_chronologically(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);

        // Crear 60 logs de latencia ordenados por tiempo
        for ($i = 60; $i >= 1; $i--) {
            LatencyLog::factory()->create([
                'service_id' => $service->id,
                'latency_ms' => 100 + $i,
                'checked_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $loadedService = $response->viewData('services')->first();

        $history = $loadedService->latency_history;
        $this->assertCount(50, $history);

        // El primer elemento debe ser más antiguo que el último (cronológico: izquierda a derecha)
        $firstLog = $history->first();
        $lastLog = $history->last();
        $this->assertTrue($firstLog->checked_at->lessThanOrEqualTo($lastLog->checked_at));

        $this->assertCount(50, $loadedService->chart_labels);
        $this->assertCount(50, $loadedService->chart_data);
    }

    public function test_open_incident_relation_is_loaded(): void
    {
        $user = User::factory()->create();
        $serviceWithIncident = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'Servicio Con Incidente',
        ]);
        $serviceHealthy = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'Servicio Sano',
        ]);

        Incident::factory()->create([
            'service_id' => $serviceWithIncident->id,
            'incident_type' => 'Down',
            'started_at' => now()->subMinutes(10),
            'resolved_at' => null,
        ]);

        Incident::factory()->create([
            'service_id' => $serviceHealthy->id,
            'incident_type' => 'Degraded',
            'started_at' => now()->subMinutes(30),
            'resolved_at' => now()->subMinutes(5), // Resuelto
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $services = $response->viewData('services')->keyBy('id');

        $this->assertNotNull($services[$serviceWithIncident->id]->openIncident);
        $this->assertSame('Down', $services[$serviceWithIncident->id]->openIncident->incident_type);

        $this->assertNull($services[$serviceHealthy->id]->openIncident);
    }
}
