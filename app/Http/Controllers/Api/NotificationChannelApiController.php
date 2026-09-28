<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationChannelResource;
use App\Models\NotificationChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class NotificationChannelApiController extends Controller
{
    /**
     * Lista los canales de notificación configurados por el usuario.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $channels = $request->user()->notificationChannels()->latest('id')->get();

        return NotificationChannelResource::collection($channels);
    }

    /**
     * Registra un nuevo canal de alertas (Email, Webhook_Discord, Webhook_Telegram).
     */
    public function store(Request $request): JsonResponse
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

        $channel = $request->user()->notificationChannels()->create($validated);

        return (new NotificationChannelResource($channel))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Muestra la información de un canal de notificación asegurando aislamiento de datos.
     */
    public function show(Request $request, NotificationChannel $notificationChannel): NotificationChannelResource
    {
        $this->authorizeChannelOwner($request, $notificationChannel);

        return new NotificationChannelResource($notificationChannel);
    }

    /**
     * Actualiza la configuración de un canal de alerta.
     */
    public function update(Request $request, NotificationChannel $notificationChannel): NotificationChannelResource
    {
        $this->authorizeChannelOwner($request, $notificationChannel);

        $validated = $request->validate([
            'type' => ['sometimes', 'required', 'string', Rule::in(['Email', 'Webhook_Discord', 'Webhook_Telegram'])],
            'target_destination' => [
                'sometimes',
                'required',
                'string',
                'max:500',
                function ($attribute, $value, $fail) use ($request, $notificationChannel) {
                    $type = $request->input('type', $notificationChannel->type);
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

        return new NotificationChannelResource($notificationChannel);
    }

    /**
     * Elimina un canal de notificación.
     */
    public function destroy(Request $request, NotificationChannel $notificationChannel): JsonResponse
    {
        $this->authorizeChannelOwner($request, $notificationChannel);

        $notificationChannel->delete();

        return response()->json([
            'message' => 'Canal de notificación eliminado correctamente.',
        ], 200);
    }

    /**
     * Valida que el canal pertenezca al usuario autenticado.
     */
    protected function authorizeChannelOwner(Request $request, NotificationChannel $channel): void
    {
        abort_if((int) $channel->user_id !== (int) $request->user()->id, 403, 'Acceso denegado a este canal.');
    }
}
