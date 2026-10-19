<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'snapshot_date',
        'total_checks',
        'up_checks',
        'uptime_sli',
        'avg_latency_ms',
        'p95_latency_ms',
        'latency_compliance_rate',
        'error_budget_percentage',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'snapshot_date' => 'date',
            'total_checks' => 'integer',
            'up_checks' => 'integer',
            'uptime_sli' => 'float',
            'avg_latency_ms' => 'integer',
            'p95_latency_ms' => 'integer',
            'latency_compliance_rate' => 'float',
            'error_budget_percentage' => 'float',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
