<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LatencyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'response_time_ms',
        'status_code',
    ];

    protected function casts(): array
    {
        return [
            'response_time_ms' => 'integer',
            'status_code' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
