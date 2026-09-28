<?php

namespace Tests\Feature;

use App\Events\EndpointStatusUpdated;
use App\Jobs\CheckEndpointStatus;
use App\Models\LatencyLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EndpointStatusBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_implements_should_broadcast_now(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);
        $latencyLog = LatencyLog::factory()->create(['service_id' => $service->id]);

        $event = new EndpointStatusUpdated($service, $latencyLog);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
    }

    public function test_event_broadcasts_on_user_private_channel(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);
        $latencyLog = LatencyLog::factory()->create(['service_id' => $service->id]);

        $event = new EndpointStatusUpdated($service, $latencyLog);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame("private-user.{$user->id}.services", $channels[0]->name);
    }

    public function test_event_broadcast_with_payload_contains_required_dashboard_metrics(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);
        $latencyLog = LatencyLog::factory()->create([
            'service_id' => $service->id,
            'latency_ms' => 145,
            'http_status_code' => 200,
            'status' => 'Up',
            'checked_at' => now(),
        ]);

        $event = new EndpointStatusUpdated($service, $latencyLog);
        $payload = $event->broadcastWith();

        $this->assertSame($service->id, $payload['service_id']);
        $this->assertSame(145, $payload['latency_ms']);
        $this->assertSame(200, $payload['http_status_code']);
        $this->assertSame('Up', $payload['status']);
        $this->assertNotNull($payload['checked_at']);
    }

    public function test_authenticated_user_can_access_their_own_services_channel(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app-id',
        ]);
        Broadcast::purge();
        require base_path('routes/channels.php');

        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => "private-user.{$user->id}.services",
            'socket_id' => '1234.5678',
        ]);

        $response->assertSuccessful();
    }

    public function test_user_cannot_access_another_users_services_channel(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app-id',
        ]);
        Broadcast::purge();
        require base_path('routes/channels.php');

        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $this->actingAs($attacker);

        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => "private-user.{$owner->id}.services",
            'socket_id' => '1234.5678',
        ]);

        $response->assertForbidden();
    }

    public function test_check_endpoint_status_job_dispatches_endpoint_status_updated_event(): void
    {
        Event::fake([EndpointStatusUpdated::class]);

        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/realtime',
        ]);

        Http::fake([
            'https://api.test/realtime' => Http::response(['status' => 'online'], 200),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        Event::assertDispatched(EndpointStatusUpdated::class, function ($event) use ($service) {
            return $event->service->id === $service->id
                && $event->latencyLog->service_id === $service->id
                && $event->latencyLog->status === 'Up'
                && $event->latencyLog->http_status_code === 200;
        });
    }
}
