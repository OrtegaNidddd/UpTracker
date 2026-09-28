<?php

namespace App\Events;

use App\Models\LatencyLog;
use App\Models\Service;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EndpointStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Crea una nueva instancia del evento.
     */
    public function __construct(
        public Service $service,
        public LatencyLog $latencyLog,
    ) {}

    /**
     * Define los canales privados por los que se transmitirá el evento.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->service->user_id}.services"),
        ];
    }

    /**
     * Datos transmitidos para el dashboard en tiempo real.
     *
     * @return array{service_id: int, latency_ms: ?int, http_status_code: ?int, status: string, checked_at: ?string}
     */
    public function broadcastWith(): array
    {
        return [
            'service_id' => $this->service->id,
            'latency_ms' => $this->latencyLog->latency_ms,
            'http_status_code' => $this->latencyLog->http_status_code,
            'status' => $this->latencyLog->status,
            'checked_at' => $this->latencyLog->checked_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
