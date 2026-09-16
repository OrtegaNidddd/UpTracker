<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'check_interval',
        'status',
        'last_check_at',
    ];

    protected function casts(): array
    {
        return [
            'last_check_at' => 'datetime',
            'check_interval' => 'integer',
        ];
    }

    // Un servicio le pertenece a un usuario (Auth de Breeze)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Un servicio tiene muchas métricas históricas de latencia
    public function latencyLogs(): HasMany
    {
        return $this->hasMany(LatencyLog::class);
    }

    // Un servicio puede tener incidentes/caídas registradas
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    // Formato legible para el intervalo de chequeo
    public function getFormattedIntervalAttribute(): string
    {
        return match ($this->check_interval) {
            30 => '30 seg',
            60 => '1 min',
            300 => '5 min',
            600 => '10 min',
            900 => '15 min',
            1800 => '30 min',
            3600 => '1 hora',
            default => "{$this->check_interval}s",
        };
    }

}
