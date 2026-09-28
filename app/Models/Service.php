<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'http_method',
        'interval_seconds',
        'latency_threshold_ms',
        'is_active',
        'check_interval',
    ];

    protected function casts(): array
    {
        return [
            'interval_seconds' => 'integer',
            'latency_threshold_ms' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Alias check_interval para compatibilidad con vistas y controladores.
     */
    protected function checkInterval(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $attributes['interval_seconds'] ?? null,
            set: fn ($value) => ['interval_seconds' => $value],
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function latencyLogs(): HasMany
    {
        return $this->hasMany(LatencyLog::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function openIncident(): HasOne
    {
        return $this->hasOne(Incident::class)->whereNull('resolved_at');
    }

    /**
     * Formato legible para el intervalo de chequeo.
     */
    public function getFormattedIntervalAttribute(): string
    {
        $seconds = $this->interval_seconds;

        return match ($seconds) {
            30 => '30 seg',
            60 => '1 min',
            300 => '5 min',
            600 => '10 min',
            900 => '15 min',
            1800 => '30 min',
            3600 => '1 hora',
            default => "{$seconds}s",
        };
    }

    /**
     * Estado dinámico calculado a partir de incidentes abiertos y logs de latencia.
     */
    public function getStatusAttribute(): string
    {
        $activeIncident = $this->relationLoaded('incidents')
            ? $this->incidents->firstWhere('resolved_at', null)
            : $this->incidents()->whereNull('resolved_at')->latest('started_at')->first();

        if ($activeIncident) {
            return $activeIncident->incident_type === 'Down' ? 'offline' : 'degraded';
        }

        $lastLog = $this->relationLoaded('latencyLogs')
            ? $this->latencyLogs->first()
            : $this->latencyLogs()->latest('checked_at')->first();

        if ($lastLog) {
            return $lastLog->status === 'Up' ? 'online' : 'offline';
        }

        return 'online';
    }

    /**
     * Timestamp del último chequeo registrado.
     */
    public function getLastCheckedAtAttribute(): ?Carbon
    {
        $lastLog = $this->relationLoaded('latencyLogs')
            ? $this->latencyLogs->first()
            : $this->latencyLogs()->latest('checked_at')->first();

        return $lastLog?->checked_at;
    }
}
