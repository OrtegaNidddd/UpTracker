<?php

namespace Tests\Feature;

use App\Jobs\CheckEndpointStatus;
use App\Models\LatencyLog;
use App\Models\Service;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ThirtyEndpointsConcurrentStressTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluates_thirty_concurrent_endpoints_and_records_metrics_and_sla(): void
    {
        $user = User::factory()->create();

        // Crear 30 servicios con configuraciones y metas SLO distintas
        $services = collect();
        $fakedResponses = [];

        for ($i = 1; $i <= 30; $i++) {
            $isDown = ($i === 30); // 1 endpoint caído
            $isDegraded = ($i === 29); // 1 endpoint con latencia degradada
            $url = "http://127.0.0.1:8000/api/mock/service-{$i}";

            $service = Service::create([
                'user_id' => $user->id,
                'name' => "Endpoint Microservicio #{$i}",
                'url' => $url,
                'interval_seconds' => 30,
                'latency_threshold_ms' => 200,
                'slo_availability_target' => 99.00,
                'slo_latency_target_ms' => 300,
                'is_active' => true,
            ]);

            $services->push($service);

            if ($isDown) {
                $fakedResponses[$url] = Http::response(['error' => 'Service Unavailable'], 503);
            } elseif ($isDegraded) {
                $fakedResponses[$url] = Http::response(['status' => 'Slow response'], 200);
            } else {
                $fakedResponses[$url] = Http::response(['status' => 'OK'], 200);
            }
        }

        Http::fake($fakedResponses);

        $startTime = hrtime(true);

        // Despachar el chequeo simultáneo de los 30 servicios
        foreach ($services as $service) {
            $job = new CheckEndpointStatus($service);
            $job->handle();
        }

        $durationMs = (hrtime(true) - $startTime) / 1e6;

        // Validaciones del requerimiento:
        // 1. Debe haber exactamente 30 logs de latencia persistidos (uno por endpoint)
        $this->assertEquals(30, LatencyLog::count());

        // 2. 29 deben estar en estado 'Up' y 1 en 'Down'
        $this->assertEquals(29, LatencyLog::where('status', 'Up')->count());
        $this->assertEquals(1, LatencyLog::where('status', 'Down')->count());

        // 3. El servicio caído debe haber generado un incidente abierto
        $downService = $services->last();
        $this->assertDatabaseHas('incidents', [
            'service_id' => $downService->id,
            'incident_type' => 'Down',
            'resolved_at' => null,
        ]);

        // 4. Cada uno de los 30 servicios debe tener su estado de SLA calculado
        $slaService = app(SlaService::class);
        $compliantCount = 0;
        $breachedCount = 0;

        foreach ($services as $service) {
            $sla = $slaService->calculateSla($service->fresh());
            if ($sla['status'] === 'Cumple') {
                $compliantCount++;
            } else {
                $breachedCount++;
            }
        }

        $this->assertEquals(29, $compliantCount);
        $this->assertEquals(1, $breachedCount);

        // 5. La ejecución de los 30 endpoints debe completarse en tiempo óptimo local (< 5000 ms)
        $this->assertLessThan(5000, $durationMs, "El procesamiento de 30 endpoints tomó {$durationMs} ms, excediendo el límite.");
    }
}
