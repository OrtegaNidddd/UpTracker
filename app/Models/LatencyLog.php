<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LatencyLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'service_id',
        'latency_ms',
        'http_status_code',
        'status',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'latency_ms' => 'integer',
            'http_status_code' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Alias response_time_ms para retrocompatibilidad.
     */
    protected function responseTimeMs(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $attributes['latency_ms'] ?? null,
            set: fn ($value) => ['latency_ms' => $value],
        );
    }

    /**
     * Alias status_code para retrocompatibilidad.
     */
    protected function statusCode(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $attributes['http_status_code'] ?? null,
            set: fn ($value) => ['http_status_code' => $value],
        );
    }
}
