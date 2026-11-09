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
     * Procesa el evento de incidente y despacha avisos exclusivamente por Correo Electrónico y WhatsApp.
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
                    'WhatsApp' => $this->sendWhatsApp($channel, $event),
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
     * Envía correo electrónico de alerta al destinatario del canal.
     */
    protected function sendEmail(NotificationChannel $channel, IncidentLogged $event): void
    {
        Mail::to($channel->target_destination)
            ->send(new ServiceAlertMail($event->incident, $event->service));
    }

    /**
     * Envía notificación mediante la API de WhatsApp (WhatsApp Cloud API / Meta Graph API)
     * con soporte de reintentos y retroceso exponencial ante respuestas HTTP 429 (Retry-After).
     */
    protected function sendWhatsApp(NotificationChannel $channel, IncidentLogged $event): void
    {
        $service = $event->service;
        $incident = $event->incident;
        $endpoint = $channel->resolveWhatsAppEndpoint();
        $token = (string) config('services.whatsapp.access_token', 'uptracker-whatsapp-token');

        $messageBody = "⚠️ Alerta UpTracker: La página {$service->name} ({$service->url}) presenta estado {$incident->incident_type}. Detalle: {$incident->details}";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $channel->target_destination,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $messageBody,
            ],
            'event' => 'incident.logged',
            'service_name' => $service->name,
            'service_url' => $service->url,
            'incident_type' => $incident->incident_type,
            'timestamp' => $incident->started_at?->toISOString() ?? now()->toISOString(),
            'details' => $incident->details,
            'content' => $messageBody,
        ];

        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = Http::timeout(10)
                ->withToken($token)
                ->asJson()
                ->post($endpoint, $payload);

            if ($response->status() !== 429) {
                return;
            }

            $retryAfterHeader = $response->header('Retry-After');
            $backoffSeconds = is_numeric($retryAfterHeader)
                ? min(5, max(1, (int) $retryAfterHeader))
                : (int) pow(2, $attempt - 1);

            Log::warning("HTTP 429 Too Many Requests en API de WhatsApp para el canal [{$channel->id}]. Reintento #{$attempt} con retroceso exponencial ({$backoffSeconds}s).", [
                'channel_id' => $channel->id,
                'retry_after' => $retryAfterHeader,
                'attempt' => $attempt,
            ]);

            if ($attempt < $maxAttempts && ! app()->runningUnitTests()) {
                sleep($backoffSeconds);
            }
        }
    }
}
