<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\LatencyLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatusApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_status_returns_operational_when_no_active_incidents(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'API Pagos',
            'is_active' => true,
        ]);

        LatencyLog::factory()->create([
            'service_id' => $service->id,
            'latency_ms' => 85,
            'http_status_code' => 200,
            'status' => 'Up',
            'checked_at' => now(),
        ]);

        $response = $this->getJson('/api/status/public');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'Operational')
            ->assertJsonPath('total_monitored', 1)
            ->assertJsonPath('online_count', 1)
            ->assertJsonPath('offline_count', 0)
            ->assertJsonPath('services.0.name', 'API Pagos')
            ->assertJsonPath('services.0.status', 'online');
    }

    public function test_public_status_returns_outage_and_lists_active_incidents(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'Base de Datos Externa',
            'is_active' => true,
        ]);

        Incident::factory()->create([
            'service_id' => $service->id,
            'incident_type' => 'Down',
            'started_at' => now()->subMinutes(15),
            'resolved_at' => null,
            'details' => 'Timeout de conexión sin respuesta.',
        ]);

        $response = $this->getJson('/status/public');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'Major_Outage')
            ->assertJsonPath('offline_count', 1)
            ->assertJsonCount(1, 'active_incidents')
            ->assertJsonPath('active_incidents.0.service_name', 'Base de Datos Externa')
            ->assertJsonPath('active_incidents.0.incident_type', 'Down');
    }

    public function test_public_status_excludes_inactive_services(): void
    {
        $user = User::factory()->create();
        Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'Servicio Desactivado',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/status/public');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'No_Services')
            ->assertJsonCount(0, 'services')
            ->assertJsonPath('sla_summary.compliant_count', 0);
    }

    public function test_public_status_page_loads_with_cookie_banner_and_header(): void
    {
        $response = $this->get('/status');

        $response->assertStatus(200)
            ->assertSee('UpTracker')
            ->assertSee('Página de Estado Pública &amp; SLA', false)
            ->assertSee('Estado de Servicios y Cumplimiento SLA')
            ->assertSee('Centro de Políticas, Cookies y Tratamiento de Datos')
            ->assertSee('Ley 1581 / 2012');
    }

    public function test_public_status_endpoint_returns_json_for_ajax_request(): void
    {
        $response = $this->get('/status/public', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'status',
                'summary',
                'total_monitored',
                'online_count',
                'sla_summary',
                'services',
            ]);
    }
}
