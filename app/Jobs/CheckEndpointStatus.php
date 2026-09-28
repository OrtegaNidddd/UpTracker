<?php

namespace App\Jobs;

use App\Events\EndpointStatusUpdated;
use App\Events\IncidentLogged;
use App\Models\LatencyLog;
use App\Models\Service;
use GuzzleHttp\TransferStats;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class CheckEndpointStatus implements ShouldQueue
{
    use Queueable;

    /**
     * El número de intentos permitidos para el job.
     */
    public int $tries = 1;

    /**
     * El número de segundos que el job puede ejecutarse antes de expirar.
     */
    public int $timeout = 15;

    /**
     * Crea una nueva instancia del Job.
     */
    public function __construct(public Service $service) {}

    /**
     * Ejecuta el sondeo HTTP y gestiona la persistencia de métricas e incidentes.
     */
    public function handle(): void
    {
        $checkResult = $this->performHttpCheck();

        $latencyLog = $this->persistLatencyLog($checkResult);

        $this->handleIncidents($checkResult);

        EndpointStatusUpdated::dispatch($this->service, $latencyLog);
    }

    /**
     * Realiza la petición HTTP aplicando timeout estricto y capturando métricas de red.
     *
     * @return array{latency_ms: ?int, http_status_code: ?int, status: string}
     */
    protected function performHttpCheck(): array
    {
        $startTime = hrtime(true);
        $ttfbMs = null;

        $method = strtoupper($this->service->http_method ?? 'GET');

        try {
            $request = Http::timeout(10)
                ->withOptions([
                    'on_stats' => function (TransferStats $stats) use (&$ttfbMs) {
                        $startTransfer = $stats->getHandlerStat('starttransfer_time');
                        if ($startTransfer !== null && $startTransfer > 0) {
                            $ttfbMs = (int) round($startTransfer * 1000);
                        }
                    },
                ]);

            $response = match ($method) {
                'HEAD' => $request->head($this->service->url),
                default => $request->get($this->service->url),
            };

            $latencyMs = $ttfbMs ?? (int) round((hrtime(true) - $startTime) / 1e6);
            $statusCode = $response->status();
            $status = ($statusCode >= 200 && $statusCode < 400) ? 'Up' : 'Down';

            return [
                'latency_ms' => $latencyMs,
                'http_status_code' => $statusCode,
                'status' => $status,
            ];
        } catch (Throwable) {
            return [
                'latency_ms' => null,
                'http_status_code' => null,
                'status' => 'Down',
            ];
        }
    }

    /**
     * Inserta un registro en la tabla latency_logs.
     *
     * @param  array{latency_ms: ?int, http_status_code: ?int, status: string}  $result
     */
    protected function persistLatencyLog(array $result): LatencyLog
    {
        return $this->service->latencyLogs()->create([
            'latency_ms' => $result['latency_ms'],
            'http_status_code' => $result['http_status_code'],
            'status' => $result['status'],
            'checked_at' => now(),
        ]);
    }

    /**
     * Gestiona la bitácora de incidentes (apertura y resolución).
     *
     * @param  array{latency_ms: ?int, http_status_code: ?int, status: string}  $result
     */
    protected function handleIncidents(array $result): void
    {
        $status = $result['status'];
        $latencyMs = $result['latency_ms'];
        $threshold = $this->service->latency_threshold_ms;

        $isDown = ($status === 'Down');
        $isDegraded = ($status === 'Up' && $threshold !== null && $latencyMs !== null && $latencyMs > $threshold);

        if ($isDown || $isDegraded) {
            $this->openIncidentIfNeeded($isDown, $latencyMs, $result['http_status_code']);
        } else {
            $this->resolveIncidentIfNeeded();
        }
    }

    /**
     * Abre un nuevo incidente si no existe uno activo previamente.
     */
    protected function openIncidentIfNeeded(bool $isDown, ?int $latencyMs, ?int $statusCode): void
    {
        $openIncident = $this->service->incidents()
            ->whereNull('resolved_at')
            ->first();

        if (! $openIncident) {
            $incidentType = $isDown ? 'Down' : 'Degraded';
            $details = $isDown
                ? ($statusCode !== null ? "Servicio caído con código HTTP {$statusCode}." : 'Fallo de conexión o timeout sin respuesta.')
                : "Latencia alta ({$latencyMs} ms) excedió el umbral ({$this->service->latency_threshold_ms} ms).";

            $newIncident = $this->service->incidents()->create([
                'incident_type' => $incidentType,
                'started_at' => now(),
                'details' => $details,
            ]);

            IncidentLogged::dispatch($newIncident, $this->service);
        }
    }

    /**
     * Resuelve el incidente abierto si el servicio está restablecido y dentro del umbral.
     */
    protected function resolveIncidentIfNeeded(): void
    {
        $openIncident = $this->service->incidents()
            ->whereNull('resolved_at')
            ->first();

        if ($openIncident) {
            $resolvedAt = now();
            $durationSeconds = $openIncident->started_at
                ? (int) $openIncident->started_at->diffInSeconds($resolvedAt)
                : null;

            $openIncident->update([
                'resolved_at' => $resolvedAt,
                'duration_seconds' => $durationSeconds,
            ]);
        }
    }
}
