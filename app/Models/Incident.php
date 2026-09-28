<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'incident_type',
        'started_at',
        'resolved_at',
        'duration_seconds',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Alias error_message para retrocompatibilidad.
     */
    protected function errorMessage(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $attributes['details'] ?? null,
            set: fn ($value) => ['details' => $value],
        );
    }
}
