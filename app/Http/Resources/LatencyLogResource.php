<?php

namespace App\Http\Resources;

use App\Models\LatencyLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LatencyLog
 */
class LatencyLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'latency_ms' => $this->latency_ms,
            'http_status_code' => $this->http_status_code,
            'status' => $this->status,
            'checked_at' => $this->checked_at?->toISOString(),
        ];
    }
}
