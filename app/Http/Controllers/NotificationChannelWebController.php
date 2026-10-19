<?php

namespace App\Http\Controllers;

use App\Mail\ServiceAlertMail;
use App\Models\Incident;
use App\Models\NotificationChannel;
use App\Models\Service;
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
     * Muestra la interfaz de administración de canales de alerta (Email, Discord, Telegram).
     */
    public function index(Request $request): View
    {
        $channels = $request->user()->notificationChannels()->latest('id')->get();

        return view('notification-channels.index', compact('channels'));
    }

    /**
     * Registra un nuevo canal de notificación.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['Email', 'Webhook_Discord', 'Webhook_Telegram'])],
            'target_destination' => [
                'required',
                'string',
                'max:500',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('type');
                    if ($type === 'Email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('El destino debe ser un correo electrónico válido.');
                    }
                    if (in_array($type, ['Webhook_Discord', 'Webhook_Telegram'], true)) {
                        $isHttpUrl = filter_var($value, FILTER_VALIDATE_URL)
                            && (str_starts_with(strtolower($value), 'http://') || str_starts_with(strtolower($value), 'https://'));
                        if (! $isHttpUrl) {
                            $fail('El destino debe ser una URL válida (http/https) para el webhook.');
                        }
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

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
     * Envía una notificación de prueba en tiempo real para verificar el canal.
     */
    public function test(Request $request, NotificationChannel $notificationChannel): RedirectResponse
    {
        abort_if((int) $notificationChannel->user_id !== (int) $request->user()->id, 403);

        try {
            $mockService = new Service([
                'name' => 'Servicio de Prueba UpTracker',
                'url' => 'https://uptracker.test/api/health',
            ]);
            $mockIncident = new Incident([
                'incident_type' => 'Degraded',
                'started_at' => now(),
                'details' => 'Envío de prueba para verificar conectividad del canal de alerta.',
            ]);

            match ($notificationChannel->type) {
                'Email' => Mail::to($notificationChannel->target_destination)
                    ->send(new ServiceAlertMail($mockIncident, $mockService)),
                'Webhook_Discord', 'Webhook_Telegram' => Http::timeout(5)
                    ->asJson()
                    ->post($notificationChannel->target_destination, [
                        'content' => '🔔 **[PRUEBA UPTRACKER]** Conexión exitosa con el canal de notificación '.$notificationChannel->type.'.',
                        'text' => '🔔 [PRUEBA UPTRACKER] Conexión exitosa con el canal de notificación '.$notificationChannel->type.'.',
                        'timestamp' => now()->toISOString(),
                    ]),
                default => null,
            };

            return redirect()->route('notification-channels.index')
                ->with('success', "Notificación de prueba enviada satisfactoriamente a {$notificationChannel->target_destination}.");
        } catch (Throwable $e) {
            return redirect()->route('notification-channels.index')
                ->with('error', "No se pudo enviar la prueba: {$e->getMessage()}");
        }
    }
}
