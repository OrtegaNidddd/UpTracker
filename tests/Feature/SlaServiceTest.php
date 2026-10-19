<?php

namespace Tests\Feature;

use App\Mail\SlaAlertMail;
use App\Models\LatencyLog;
use App\Models\NotificationChannel;
use App\Models\Service;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SlaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SlaService $slaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->slaService = app(SlaService::class);
    }

    public function test_service_complies_when_all_checks_are_up(): void
    {
        $user = User::factory()->create();
        $service = Service::create([
            'user_id' => $user->id,
            'name' => 'API Pagos',
            'url' => 'https://api.pagos.com/health',
            'interval_seconds' => 60,
            'slo_availability_target' => 99.00,
            'slo_latency_target_ms' => 400,
            'is_active' => true,
        ]);

        // Insertar 10 chequeos exitosos y rápidos
        for ($i = 0; $i < 10; $i++) {
            LatencyLog::create([
                'service_id' => $service->id,
                'status' => 'Up',
                'latency_ms' => 120,
                'http_status_code' => 200,
                'checked_at' => now()->subMinutes($i * 5),
            ]);
        }

        $result = $this->slaService->calculateSla($service, 30);

        $this->assertEquals(100.0, $result['uptime_sli']);
        $this->assertEquals(100.0, $result['error_budget_percentage']);
        $this->assertEquals('Cumple', $result['status']);
        $this->assertEquals(120, $result['avg_latency_ms']);
    }

    public function test_service_is_at_risk_when_error_budget_is_low_or_latency_is_high(): void
    {
        $user = User::factory()->create();
        $service = Service::create([
            'user_id' => $user->id,
            'name' => 'Microservicio Stock',
            'url' => 'https://api.stock.com/health',
            'interval_seconds' => 60,
            'slo_availability_target' => 99.00,
            'slo_latency_target_ms' => 200,
            'is_active' => true,
        ]);

        // Insertar chequeos con latencia degradada cercana al límite (avg 180 ms >= 85% de 200)
        for ($i = 0; $i < 10; $i++) {
            LatencyLog::create([
                'service_id' => $service->id,
                'status' => 'Up',
                'latency_ms' => 190,
                'http_status_code' => 200,
                'checked_at' => now()->subMinutes($i * 5),
            ]);
        }

        $result = $this->slaService->calculateSla($service, 30);

        $this->assertEquals('En riesgo', $result['status']);
        $this->assertStringContainsString('en riesgo preventivo', $result['message']);
    }

    public function test_service_breaches_sla_when_availability_drops_below_slo(): void
    {
        $user = User::factory()->create();
        $service = Service::create([
            'user_id' => $user->id,
            'name' => 'API Notificaciones',
            'url' => 'https://api.notif.com/health',
            'interval_seconds' => 60,
            'slo_availability_target' => 99.00,
            'slo_latency_target_ms' => 500,
            'is_active' => true,
        ]);

        // 8 Up y 2 Down (80% disponibilidad vs 99% requerido)
        for ($i = 0; $i < 8; $i++) {
            LatencyLog::create([
                'service_id' => $service->id,
                'status' => 'Up',
                'latency_ms' => 100,
                'http_status_code' => 200,
                'checked_at' => now()->subMinutes($i * 5),
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            LatencyLog::create([
                'service_id' => $service->id,
                'status' => 'Down',
                'latency_ms' => null,
                'http_status_code' => 500,
                'checked_at' => now()->subMinutes(50 + $i * 5),
            ]);
        }

        $result = $this->slaService->calculateSla($service, 30);

        $this->assertEquals(80.0, $result['uptime_sli']);
        $this->assertEquals(0.0, $result['error_budget_percentage']);
        $this->assertEquals('Incumple', $result['status']);
    }

    public function test_preventive_alert_dispatched_to_notification_channels(): void
    {
        Mail::fake();
        Http::fake();

        $user = User::factory()->create();
        $service = Service::create([
            'user_id' => $user->id,
            'name' => 'Servicio Crítico Facturación',
            'url' => 'https://api.facturas.com/health',
            'interval_seconds' => 60,
            'sla_status' => 'Cumple',
            'slo_availability_target' => 99.00,
            'slo_latency_target_ms' => 200,
            'is_active' => true,
        ]);

        // Canales configurados
        NotificationChannel::create([
            'user_id' => $user->id,
            'type' => 'Email',
            'target_destination' => 'devops@empresa.com',
            'is_active' => true,
        ]);

        NotificationChannel::create([
            'user_id' => $user->id,
            'type' => 'Webhook_Discord',
            'target_destination' => 'https://discord.com/api/webhooks/123/abc',
            'is_active' => true,
        ]);

        // Causar que caiga en "Incumple"
        for ($i = 0; $i < 5; $i++) {
            LatencyLog::create([
                'service_id' => $service->id,
                'status' => 'Down',
                'latency_ms' => null,
                'http_status_code' => 503,
                'checked_at' => now()->subMinutes($i),
            ]);
        }

        $this->slaService->evaluateAndSyncService($service);

        $this->assertEquals('Incumple', $service->fresh()->sla_status);

        // Validar envío de correo preventivo
        Mail::assertSent(SlaAlertMail::class, function ($mail) {
            return $mail->hasTo('devops@empresa.com') && $mail->slaData['status'] === 'Incumple';
        });

        // Validar webhook Discord
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com')
                && str_contains($request['content'], 'Incumple');
        });
    }

    public function test_record_daily_snapshot_persists_in_sla_histories_table(): void
    {
        $user = User::factory()->create();
        $service = Service::create([
            'user_id' => $user->id,
            'name' => 'Servicio Snapshot',
            'url' => 'https://snapshot.test/api',
            'interval_seconds' => 60,
            'slo_availability_target' => 99.00,
            'slo_latency_target_ms' => 300,
            'is_active' => true,
        ]);

        LatencyLog::create([
            'service_id' => $service->id,
            'status' => 'Up',
            'latency_ms' => 150,
            'http_status_code' => 200,
            'checked_at' => now(),
        ]);

        $snapshot = $this->slaService->recordDailySnapshot($service);

        $this->assertDatabaseHas('sla_histories', [
            'service_id' => $service->id,
            'total_checks' => 1,
            'up_checks' => 1,
            'uptime_sli' => 100.0,
            'status' => 'Cumple',
        ]);
        $this->assertEquals(now()->toDateString(), $snapshot->snapshot_date->toDateString());
        $this->assertEquals(100.0, $snapshot->uptime_sli);
    }
}
