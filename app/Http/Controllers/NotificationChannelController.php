<?php

namespace App\Http\Controllers;

use App\Models\NotificationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationChannelController extends Controller
{
    /**
     * Muestra la lista de canales de alerta configurados por el usuario.
     */
    public function index(Request $request): View
    {
        $channels = $request->user()->notificationChannels()->latest('id')->get();

        return view('channels.index', compact('channels'));
    }

    /**
     * Guarda un nuevo canal de notificación (Email, Webhook_Discord, Webhook_Telegram).
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
                        $fail('El destino debe ser una dirección de correo válida para el tipo Email.');
                    }
                    if (in_array($type, ['Webhook_Discord', 'Webhook_Telegram'], true)) {
                        $isHttpUrl = filter_var($value, FILTER_VALIDATE_URL)
                            && (str_starts_with(strtolower($value), 'http://') || str_starts_with(strtolower($value), 'https://'));
                        if (! $isHttpUrl) {
                            $fail('El destino debe ser una URL válida (http/https) para los canales webhook.');
                        }
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $request->user()->notificationChannels()->create($validated);

        return redirect()->route('notification-channels.index')
            ->with('success', 'Canal de alerta registrado exitosamente.');
    }

    /**
     * Actualiza el destino o tipo de un canal existente.
     */
    public function update(Request $request, NotificationChannel $notificationChannel): RedirectResponse
    {
        abort_if((int) $notificationChannel->user_id !== (int) $request->user()->id, 403);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['Email', 'Webhook_Discord', 'Webhook_Telegram'])],
            'target_destination' => [
                'required',
                'string',
                'max:500',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('type');
                    if ($type === 'Email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('El destino debe ser una dirección de correo válida para el tipo Email.');
                    }
                    if (in_array($type, ['Webhook_Discord', 'Webhook_Telegram'], true)) {
                        $isHttpUrl = filter_var($value, FILTER_VALIDATE_URL)
                            && (str_starts_with(strtolower($value), 'http://') || str_starts_with(strtolower($value), 'https://'));
                        if (! $isHttpUrl) {
                            $fail('El destino debe ser una URL válida (http/https) para los canales webhook.');
                        }
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $notificationChannel->update($validated);

        return redirect()->route('notification-channels.index')
            ->with('success', 'Canal de alerta actualizado correctamente.');
    }

    /**
     * Alterna rápidamente el estado activo/inactivo del canal.
     */
    public function toggle(Request $request, NotificationChannel $notificationChannel): RedirectResponse
    {
        abort_if((int) $notificationChannel->user_id !== (int) $request->user()->id, 403);

        $notificationChannel->update([
            'is_active' => ! $notificationChannel->is_active,
        ]);

        $statusText = $notificationChannel->is_active ? 'activado' : 'pausado';

        return redirect()->route('notification-channels.index')
            ->with('success', "Canal de alerta {$statusText}.");
    }

    /**
     * Elimina un canal de alerta.
     */
    public function destroy(Request $request, NotificationChannel $notificationChannel): RedirectResponse
    {
        abort_if((int) $notificationChannel->user_id !== (int) $request->user()->id, 403);

        $notificationChannel->delete();

        return redirect()->route('notification-channels.index')
            ->with('success', 'Canal de alerta eliminado.');
    }
}
