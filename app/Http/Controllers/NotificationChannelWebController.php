<?php

namespace App\Http\Controllers;

use App\Mail\ServiceAlertMail;
use App\Models\Incident;
use App\Models\NotificationChannel;
use App\Models\Service;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class NotificationChannelWebController extends Controller
{
    /**
     * Muestra la interfaz de administración de canales de alerta (exclusivamente Correo Electrónico y WhatsApp).
     */
    public function index(Request $request): View
    {
        $channels = $request->user()->notificationChannels()->latest('id')->get();

        return view('notification-channels.index', compact('channels'));
    }

    /**
     * Registra un nuevo canal de notificación (Email o WhatsApp).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(NotificationChannel::ALLOWED_TYPES)],
            'target_destination' => [
                'required',
                'string',
                'max:500',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('type');
                    if ($type === 'Email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('El destino debe ser un correo electrónico válido.');
                    }
                    if ($type === 'WhatsApp' && ! NotificationChannel::isValidWhatsAppDestination((string) $value)) {
                        $fail('El destino debe ser un número de WhatsApp válido (ej: +573001234567) o endpoint de la API de WhatsApp.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['target_destination'] = NotificationChannel::normalizeDestination($validated['type'], (string) $validated['target_destination']);

        $request->user()->notificationChannels()->create($validated);

        return redirect()->route('notification-channels.index')
            ->with('success', "Canal {$validated['type']} configurado correctamente.");
    }

    /**
     * Alterna o actualiza el estado de un canal.
     */
    public function update(Request $request, NotificationChannel $notificationChannel): RedirectResponse
    {
        abort_if((int) $notificationChannel->user_id !== (int) $request->user()->id, 403);

        $notificationChannel->update([
            'is_active' => ! $notificationChannel->is_active,
        ]);

        return redirect()->route('notification-channels.index')
            ->with('success', 'Estado del canal actualizado.');
    }

    /**
     * Elimina el canal de notificación.
     */
    public function destroy(Request $request, NotificationChannel $notificationChannel): RedirectResponse
    {
        abort_if((int) $notificationChannel->user_id !== (int) $request->user()->id, 403);

        $notificationChannel->delete();

        return redirect()->route('notification-channels.index')
            ->with('success', 'Canal de notificación eliminado.');
    }

    /**
     * Envía una notificación de prueba en tiempo real para verificar el canal (Correo Electrónico o WhatsApp).
     */
    public function test(Request $request, NotificationChannel $notificationChannel): RedirectResponse
    {
        abort_if((int) $notificationChannel->user_id !== (int) $request->user()->id, 403);

        try {
            $mockService = new Service([
                'name' => 'Página Web de Prueba UpTracker',
                'url' => 'https://uptracker.test/api/health',
            ]);
            $mockIncident = new Incident([
                'incident_type' => 'Degraded',
                'started_at' => now(),
                'details' => 'Envío de prueba para verificar que los avisos de UpTracker lleguen correctamente.',
            ]);

            $defaultMailer = config('mail.default');
            $smtpUser = config('mail.mailers.smtp.username');
            $isUnconfiguredSmtp = $defaultMailer === 'smtp' && (empty($smtpUser) || $smtpUser === 'null');

            $response = match ($notificationChannel->type) {
                'Email' => $isUnconfiguredSmtp
                    ? Mail::mailer('log')->to($notificationChannel->target_destination)->send(new ServiceAlertMail($mockIncident, $mockService))
                    : Mail::to($notificationChannel->target_destination)->send(new ServiceAlertMail($mockIncident, $mockService)),
                'WhatsApp' => Http::timeout(10)
                    ->withToken((string) config('services.whatsapp.access_token', 'uptracker-whatsapp-token'))
                    ->asJson()
                    ->post($notificationChannel->resolveWhatsAppEndpoint(), [
                        'messaging_product' => 'whatsapp',
                        'to' => $notificationChannel->target_destination,
                        'type' => 'text',
                        'text' => [
                            'body' => '🔔 [PRUEBA UPTRACKER] Conexión exitosa con el canal de avisos por WhatsApp.',
                        ],
                        'content' => '🔔 [PRUEBA UPTRACKER] Conexión exitosa con el canal de avisos por WhatsApp.',
                        'timestamp' => now()->toISOString(),
                    ]),
                default => null,
            };

            if ($notificationChannel->type === 'WhatsApp' && $response instanceof Response && ! $response->successful()) {
                $err = $response->json('error.error_data.details') ?? $response->json('error.message') ?? 'Error en la API de WhatsApp';

                return redirect()->route('notification-channels.index')
                    ->with('error', "No se pudo enviar por WhatsApp: {$err}");
            }

            $successMessage = $notificationChannel->type === 'Email' && $isUnconfiguredSmtp
                ? 'Notificación de prueba generada exitosamente en el log local (storage/logs/laravel.log). Para entrega directa a tu bandeja de correo, configura MAIL_USERNAME y MAIL_PASSWORD en tu archivo .env.'
                : "Notificación de prueba enviada satisfactoriamente a {$notificationChannel->target_destination}.";

            return redirect()->route('notification-channels.index')
                ->with('success', $successMessage);
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (str_contains($message, '530') || str_contains($message, 'Authentication required')) {
                $message = 'Error de autenticación SMTP: Las credenciales de correo (MAIL_USERNAME / MAIL_PASSWORD) no son válidas o están vacías en tu archivo .env. Configura tus credenciales o usa MAIL_MAILER=log.';
            }

            return redirect()->route('notification-channels.index')
                ->with('error', "No se pudo enviar la prueba: {$message}");
        }
    }
}
