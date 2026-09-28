<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'url' => $this->url,
            'http_method' => $this->http_method ?? 'GET',
            'interval_seconds' => $this->interval_seconds,
            'formatted_interval' => $this->formatted_interval,
            'latency_threshold_ms' => $this->latency_threshold_ms,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'last_checked_at' => $this->last_checked_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'latest_logs' => LatencyLogResource::collection($this->whenLoaded('latencyLogs')),
            'incidents' => IncidentResource::collection($this->whenLoaded('incidents')),
        ];
    }
}
