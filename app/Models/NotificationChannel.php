<?php

namespace App\Models;

use Database\Factories\NotificationChannelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationChannel extends Model
{
    /** @use HasFactory<NotificationChannelFactory> */
    use HasFactory;

    public const ALLOWED_TYPES = ['Email', 'WhatsApp'];

    protected $fillable = [
        'user_id',
        'type',
        'target_destination',
        'is_active',
    ];

    /**
     * Valida si el destino proporcionado es válido para WhatsApp (teléfono internacional E.164 o URL de API WhatsApp).
     */
    public static function isValidWhatsAppDestination(string $value): bool
    {
        $trimmed = trim($value);

        // Permite número de teléfono internacional (ej: +573001234567 o 573001234567)
        if (preg_match('/^\+?[1-9]\d{6,14}$/', $trimmed) === 1) {
            return true;
        }

        // O bien endpoint HTTPS/HTTP directo de WhatsApp Cloud API / Meta Graph API
        return filter_var($trimmed, FILTER_VALIDATE_URL) !== false
            && (str_starts_with(strtolower($trimmed), 'http://') || str_starts_with(strtolower($trimmed), 'https://'));
    }

    /**
     * Resuelve el endpoint HTTP de la API de WhatsApp hacia donde enviar el mensaje.
     */
    public function resolveWhatsAppEndpoint(): string
    {
        $destination = trim($this->target_destination);

        if (str_starts_with(strtolower($destination), 'http://') || str_starts_with(strtolower($destination), 'https://')) {
            return $destination;
        }

        $baseUrl = rtrim((string) config('services.whatsapp.api_url', 'https://graph.facebook.com/v20.0'), '/');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id', '100000000000000');

        return "{$baseUrl}/{$phoneNumberId}/messages";
    }

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
