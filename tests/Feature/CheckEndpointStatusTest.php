<?php

namespace Tests\Feature;

use App\Jobs\CheckEndpointStatus;
use App\Models\Incident;
use App\Models\Service;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CheckEndpointStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_records_up_status_and_latency_on_200_ok(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/health',
            'latency_threshold_ms' => 1000,
        ]);

        Http::fake([
            'https://api.test/health' => Http::response(['status' => 'healthy'], 200),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        $this->assertDatabaseHas('latency_logs', [
            'service_id' => $service->id,
            'http_status_code' => 200,
            'status' => 'Up',
        ]);

        $log = $service->latencyLogs()->first();
        $this->assertNotNull($log);
        $this->assertNotNull($log->latency_ms);
        $this->assertGreaterThanOrEqual(0, $log->latency_ms);

        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_job_records_up_status_on_redirect_3xx(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/redirect',
        ]);

        Http::fake([
            'https://api.test/redirect' => Http::response(null, 301),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        $this->assertDatabaseHas('latency_logs', [
            'service_id' => $service->id,
            'http_status_code' => 301,
            'status' => 'Up',
        ]);
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_job_records_down_status_and_opens_incident_on_500_error(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/failing',
        ]);

        Http::fake([
            'https://api.test/failing' => Http::response('Server Error', 500),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        $this->assertDatabaseHas('latency_logs', [
            'service_id' => $service->id,
            'http_status_code' => 500,
            'status' => 'Down',
        ]);

        $this->assertDatabaseHas('incidents', [
            'service_id' => $service->id,
            'incident_type' => 'Down',
            'resolved_at' => null,
        ]);
    }

    public function test_job_records_down_status_on_4xx_client_error(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/not-found',
        ]);

        Http::fake([
            'https://api.test/not-found' => Http::response('Not Found', 404),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        $this->assertDatabaseHas('latency_logs', [
            'service_id' => $service->id,
            'http_status_code' => 404,
            'status' => 'Down',
        ]);

        $this->assertDatabaseHas('incidents', [
            'service_id' => $service->id,
            'incident_type' => 'Down',
            'resolved_at' => null,
        ]);
    }

    public function test_job_handles_network_exceptions_without_failing_and_records_down(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/timeout',
        ]);

        Http::fake([
            'https://api.test/timeout' => fn () => throw new ConnectionException('cURL error 28: Operation timed out after 10000 milliseconds with 0 bytes received'),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        $this->assertDatabaseHas('latency_logs', [
            'service_id' => $service->id,
            'http_status_code' => null,
            'latency_ms' => null,
            'status' => 'Down',
        ]);

        $this->assertDatabaseHas('incidents', [
            'service_id' => $service->id,
            'incident_type' => 'Down',
            'resolved_at' => null,
        ]);
    }

    public function test_job_opens_degraded_incident_when_latency_exceeds_threshold(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/slow',
            'latency_threshold_ms' => 10,
        ]);

        Http::fake([
            'https://api.test/slow' => function () {
                usleep(25000); // 25ms para superar el umbral de 10ms

                return Http::response('Slow response', 200);
            },
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        $this->assertDatabaseHas('latency_logs', [
            'service_id' => $service->id,
            'http_status_code' => 200,
            'status' => 'Up',
        ]);

        $this->assertDatabaseHas('incidents', [
            'service_id' => $service->id,
            'incident_type' => 'Degraded',
            'resolved_at' => null,
        ]);
    }

    public function test_job_does_not_duplicate_open_incidents(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/down',
        ]);

        Incident::factory()->create([
            'service_id' => $service->id,
            'incident_type' => 'Down',
            'started_at' => now()->subMinutes(10),
            'resolved_at' => null,
        ]);

        Http::fake([
            'https://api.test/down' => Http::response(null, 503),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        $this->assertSame(1, $service->incidents()->count());
    }

    public function test_job_resolves_open_incident_when_service_recovers(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/recovered',
            'latency_threshold_ms' => 1000,
        ]);

        $incident = Incident::factory()->create([
            'service_id' => $service->id,
            'incident_type' => 'Down',
            'started_at' => now()->subSeconds(120),
            'resolved_at' => null,
            'duration_seconds' => null,
        ]);

        Http::fake([
            'https://api.test/recovered' => Http::response(['status' => 'ok'], 200),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        $incident->refresh();

        $this->assertNotNull($incident->resolved_at);
        $this->assertNotNull($incident->duration_seconds);
        $this->assertGreaterThanOrEqual(119, $incident->duration_seconds);
    }

    public function test_poll_active_endpoints_command_dispatches_jobs_only_for_active_services(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $activeService1 = Service::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);
        $activeService2 = Service::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);
        $inactiveService = Service::factory()->create([
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        $this->artisan('monitor:poll', ['--force' => true])
            ->assertSuccessful();

        Queue::assertPushed(CheckEndpointStatus::class, 2);
        Queue::assertPushed(CheckEndpointStatus::class, function ($job) use ($activeService1) {
            return $job->service->id === $activeService1->id;
        });
        Queue::assertPushed(CheckEndpointStatus::class, function ($job) use ($activeService2) {
            return $job->service->id === $activeService2->id;
        });
        Queue::assertNotPushed(CheckEndpointStatus::class, function ($job) use ($inactiveService) {
            return $job->service->id === $inactiveService->id;
        });
    }

    public function test_scheduler_registers_job_for_active_services(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'interval_seconds' => 30,
        ]);

        require base_path('routes/console.php');

        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $this->assertNotEmpty($events);
    }

    public function test_job_supports_head_http_method(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/head-check',
            'http_method' => 'HEAD',
        ]);

        Http::fake([
            'https://api.test/head-check' => Http::response(null, 200),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        $this->assertDatabaseHas('latency_logs', [
            'service_id' => $service->id,
            'http_status_code' => 200,
            'status' => 'Up',
        ]);

        Http::assertSent(function ($request) {
            return $request->method() === 'HEAD' && $request->url() === 'https://api.test/head-check';
        });
    }

    public function test_job_sends_custom_headers(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/secure-endpoint',
            'custom_headers' => [
                'Authorization' => 'Bearer secret-token',
                'X-Custom-Probe' => 'UpTracker',
            ],
        ]);

        Http::fake([
            'https://api.test/secure-endpoint' => Http::response(['status' => 'authorized'], 200),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request->hasHeader('X-Custom-Probe', 'UpTracker');
        });
    }
}
