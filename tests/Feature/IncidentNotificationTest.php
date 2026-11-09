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

    public function test_dispatch_incident_notifications_sends_whatsapp_messages_to_active_whatsapp_channels(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]], 200),
        ]);

        $user = User::factory()->create();
        $service = Service::factory()->create([
            'user_id' => $user->id,
            'name' => 'Página Web Comercio Local',
            'url' => 'https://comercio.local.test/health',
        ]);
        $incident = Incident::factory()->create([
            'service_id' => $service->id,
            'incident_type' => 'Degraded',
            'details' => 'Tiempo de respuesta de 1250ms excedió el umbral.',
        ]);

        $whatsappChannel = NotificationChannel::factory()->whatsapp()->create([
            'user_id' => $user->id,
            'target_destination' => '+573001234567',
            'is_active' => true,
        ]);

        $listener = new DispatchIncidentNotifications;
        $listener->handle(new IncidentLogged($incident, $service));

        Http::assertSent(function ($request) use ($whatsappChannel, $service) {
            return str_contains($request->url(), 'graph.facebook.com')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === $whatsappChannel->target_destination
                && $request['service_name'] === $service->name
                && $request['incident_type'] === 'Degraded';
        });
    }

    public function test_dispatch_incident_notifications_retries_on_http_429_rate_limit(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::sequence()
                ->push(['error' => 'Too Many Requests'], 429, ['Retry-After' => '1'])
                ->push(['messages' => [['id' => 'wamid.retry.ok']]], 200),
        ]);

        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);
        $incident = Incident::factory()->create(['service_id' => $service->id]);

        NotificationChannel::factory()->whatsapp()->create([
            'user_id' => $user->id,
            'target_destination' => '+573109876543',
            'is_active' => true,
        ]);

        $listener = new DispatchIncidentNotifications;
        $listener->handle(new IncidentLogged($incident, $service));

        Http::assertSentCount(2);
    }

    public function test_listener_handles_external_service_failures_gracefully(): void
    {
        Http::fake([
            'https://failing-whatsapp.test/*' => fn () => throw new ConnectionException('Timeout sending WhatsApp message'),
            'https://success-whatsapp.test/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);
        $incident = Incident::factory()->create(['service_id' => $service->id]);

        NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => 'WhatsApp',
            'target_destination' => 'https://failing-whatsapp.test/api',
            'is_active' => true,
        ]);

        NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => 'WhatsApp',
            'target_destination' => 'https://success-whatsapp.test/api',
            'is_active' => true,
        ]);

        $listener = new DispatchIncidentNotifications;
        // Debe ejecutarse sin lanzar excepciones no controladas
        $listener->handle(new IncidentLogged($incident, $service));

        Http::assertSent(fn ($request) => $request->url() === 'https://success-whatsapp.test/api');
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
