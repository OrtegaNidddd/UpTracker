<?php

namespace Tests\Feature;

use App\Events\IncidentLogged;
use App\Jobs\CheckEndpointStatus;
use App\Listeners\DispatchIncidentNotifications;
use App\Mail\ServiceAlertMail;
use App\Models\Incident;
use App\Models\NotificationChannel;
use App\Models\Service;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IncidentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_implements_should_queue(): void
    {
        $listener = new DispatchIncidentNotifications;

        $this->assertInstanceOf(ShouldQueue::class, $listener);
    }

    public function test_incident_logged_event_dispatched_only_when_new_incident_is_created(): void
    {
        Event::fake([IncidentLogged::class]);

        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/failure',
        ]);

        Http::fake([
            'https://api.test/failure' => Http::response('Server Error', 500),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        Event::assertDispatched(IncidentLogged::class, function ($event) use ($service) {
            return $event->service->id === $service->id
                && $event->incident->incident_type === 'Down';
        });
    }

    public function test_incident_logged_event_not_dispatched_if_incident_already_open(): void
    {
        Event::fake([IncidentLogged::class]);

        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://api.test/still-down',
        ]);

        Incident::factory()->create([
            'service_id' => $service->id,
            'incident_type' => 'Down',
            'started_at' => now()->subMinutes(15),
            'resolved_at' => null,
        ]);

        Http::fake([
            'https://api.test/still-down' => Http::response('Server Error', 500),
        ]);

        $job = new CheckEndpointStatus($service);
        $job->handle();

        Event::assertNotDispatched(IncidentLogged::class);
    }

    public function test_dispatch_incident_notifications_sends_email_to_active_email_channels(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);
        $incident = Incident::factory()->create([
            'service_id' => $service->id,
            'incident_type' => 'Down',
        ]);

        $activeChannel = NotificationChannel::factory()->email()->create([
            'user_id' => $user->id,
            'target_destination' => 'alerts@empresa.com',
            'is_active' => true,
        ]);

        $inactiveChannel = NotificationChannel::factory()->email()->inactive()->create([
            'user_id' => $user->id,
            'target_destination' => 'inactive@empresa.com',
        ]);

        $listener = new DispatchIncidentNotifications;
        $listener->handle(new IncidentLogged($incident, $service));

        Mail::assertSent(ServiceAlertMail::class, function (ServiceAlertMail $mail) use ($activeChannel) {
            return $mail->hasTo($activeChannel->target_destination);
        });

        Mail::assertNotSent(ServiceAlertMail::class, function (ServiceAlertMail $mail) use ($inactiveChannel) {
            return $mail->hasTo($inactiveChannel->target_destination);
        });
    }

    public function test_dispatch_incident_notifications_sends_webhooks_to_discord_and_telegram_channels(): void
    {
        Http::fake([
            'https://discord.com/api/webhooks/*' => Http::response(['status' => 'ok'], 204),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'API Auth Microservice',
            'url' => 'https://auth.internal.corp/health',
        ]);
        $incident = Incident::factory()->create([
            'service_id' => $service->id,
            'incident_type' => 'Degraded',
            'details' => 'Latencia de 1250ms excedió el umbral.',
        ]);

        $discordChannel = NotificationChannel::factory()->discord()->create([
            'user_id' => $user->id,
            'target_destination' => 'https://discord.com/api/webhooks/12345/abcdefg',
            'is_active' => true,
        ]);

        $telegramChannel = NotificationChannel::factory()->telegram()->create([
            'user_id' => $user->id,
            'target_destination' => 'https://api.telegram.org/bot123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11/sendMessage',
            'is_active' => true,
        ]);

        $listener = new DispatchIncidentNotifications;
        $listener->handle(new IncidentLogged($incident, $service));

        Http::assertSent(function ($request) use ($discordChannel, $service) {
            return $request->url() === $discordChannel->target_destination
                && $request['service_name'] === $service->name
                && $request['incident_type'] === 'Degraded'
                && str_contains($request['service_url'], 'auth.internal.corp');
        });

        Http::assertSent(function ($request) use ($telegramChannel, $service) {
            return $request->url() === $telegramChannel->target_destination
                && $request['service_name'] === $service->name
                && $request['incident_type'] === 'Degraded';
        });
    }

    public function test_listener_handles_external_service_failures_gracefully(): void
    {
        Http::fake([
            'https://failing-webhook.test/*' => fn () => throw new ConnectionException('Timeout sending webhook'),
            'https://success-webhook.test/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);
        $incident = Incident::factory()->create(['service_id' => $service->id]);

        NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => 'Webhook_Discord',
            'target_destination' => 'https://failing-webhook.test/api',
            'is_active' => true,
        ]);

        NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => 'Webhook_Telegram',
            'target_destination' => 'https://success-webhook.test/api',
            'is_active' => true,
        ]);

        $listener = new DispatchIncidentNotifications;
        // Debe ejecutarse sin lanzar excepciones no controladas
        $listener->handle(new IncidentLogged($incident, $service));

        Http::assertSent(fn ($request) => $request->url() === 'https://success-webhook.test/api');
    }

    public function test_service_alert_mail_renders_correctly(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'Gateway de Pagos',
            'url' => 'https://payments.gateway.com/health',
        ]);
        $incident = Incident::factory()->create([
            'service_id' => $service->id,
            'incident_type' => 'Down',
            'details' => 'Fallo HTTP 502 Bad Gateway',
            'started_at' => now(),
        ]);

        $mailable = new ServiceAlertMail($incident, $service);
        $mailable->assertSeeInHtml($service->name);
        $mailable->assertSeeInHtml($service->url);
        $mailable->assertSeeInHtml('Down');
        $mailable->assertSeeInHtml('Fallo HTTP 502 Bad Gateway');
    }
}
