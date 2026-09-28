<?php

namespace App\Listeners;

use App\Events\IncidentLogged;
use App\Mail\ServiceAlertMail;
use App\Models\NotificationChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DispatchIncidentNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Número de intentos del listener.
     */
    public int $tries = 3;

    /**
     * Límite de tiempo en segundos para procesar las notificaciones.
     */
    public int $timeout = 30;

    /**
     * Procesa el evento de incidente y despacha correos y webhooks.
     */
    public function handle(IncidentLogged $event): void
    {
        $channels = NotificationChannel::query()
            ->where('user_id', $event->service->user_id)
            ->where('is_active', true)
            ->get();

        foreach ($channels as $channel) {
            try {
                match ($channel->type) {
                    'Email' => $this->sendEmail($channel, $event),
                    'Webhook_Discord', 'Webhook_Telegram' => $this->sendWebhook($channel, $event),
                    default => null,
                };
            } catch (Throwable $e) {
                Log::error("Error despachando notificación al canal [{$channel->id} - {$channel->type}]: {$e->getMessage()}", [
                    'channel_id' => $channel->id,
                    'incident_id' => $event->incident->id,
                    'service_id' => $event->service->id,
                ]);
            }
        }
    }

    /**
     * Envía correo de alerta al destinatario del canal.
     */
    protected function sendEmail(NotificationChannel $channel, IncidentLogged $event): void
    {
        Mail::to($channel->target_destination)
            ->send(new ServiceAlertMail($event->incident, $event->service));
    }

    /**
     * Envía petición POST con payload estructurado hacia el webhook de destino.
     */
    protected function sendWebhook(NotificationChannel $channel, IncidentLogged $event): void
    {
        $service = $event->service;
        $incident = $event->incident;

        $payload = [
            'event' => 'incident.logged',
            'service_name' => $service->name,
            'service_url' => $service->url,
            'incident_type' => $incident->incident_type,
            'timestamp' => $incident->started_at?->toISOString() ?? now()->toISOString(),
            'details' => $incident->details,
            // Campos amigables con APIs de mensajería (Discord / Telegram)
            'content' => "⚠️ **Alerta UpTracker**: El servicio `{$service->name}` se encuentra en estado **{$incident->incident_type}** ({$service->url}).",
            'text' => "⚠️ Alerta UpTracker: El servicio {$service->name} se encuentra en estado {$incident->incident_type} ({$service->url}).",
        ];

        Http::timeout(10)
            ->asJson()
            ->post($channel->target_destination, $payload);
    }
}
