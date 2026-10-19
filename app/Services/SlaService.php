<?php

namespace App\Services;

use App\Mail\SlaAlertMail;
use App\Models\LatencyLog;
use App\Models\NotificationChannel;
use App\Models\Service;
use App\Models\SlaHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SlaService
{
    /**
     * Calcula métricas SLA/SLO (SLI de disponibilidad, latencia, presupuesto de error y clasificación).
     *
     * @return array{
     *     service_id: int,
     *     period_days: int,
     *     slo_availability_target: float,
     *     slo_latency_target_ms: int,
     *     uptime_sli: float,
     *     avg_latency_ms: int,
     *     p95_latency_ms: int,
     *     latency_compliance_rate: float,
     *     total_checks: int,
     *     up_checks: int,
     *     fast_checks: int,
     *     error_budget_percentage: float,
     *     status: string,
     *     message: string
     * }
     */
    public function calculateSla(Service $service, int $periodDays = 30): array
    {
        $since = now()->subDays($periodDays);

        $logs = LatencyLog::query()
            ->where('service_id', $service->id)
            ->where('checked_at', '>=', $since)
            ->get(['status', 'latency_ms', 'checked_at']);

        $totalChecks = $logs->count();
        $targetAvailability = (float) ($service->slo_availability_target ?? 99.00);
        $targetLatencyMs = (int) ($service->slo_latency_target_ms ?? 500);

        if ($totalChecks === 0) {
            return [
                'service_id' => $service->id,
                'period_days' => $periodDays,
                'slo_availability_target' => $targetAvailability,
                'slo_latency_target_ms' => $targetLatencyMs,
                'uptime_sli' => 100.0,
                'avg_latency_ms' => 0,
                'p95_latency_ms' => 0,
                'latency_compliance_rate' => 100.0,
                'total_checks' => 0,
                'up_checks' => 0,
                'fast_checks' => 0,
                'error_budget_percentage' => 100.0,
                'status' => 'Cumple',
                'message' => 'Sin verificaciones registradas aún. El servicio se encuentra en cumplimiento nominal.',
            ];
        }

        $upChecks = $logs->where('status', 'Up')->count();
        $uptimeSli = round(($upChecks / $totalChecks) * 100, 2);

        $validLatencies = $logs->whereNotNull('latency_ms')->pluck('latency_ms')->sort()->values();
        $avgLatencyMs = $validLatencies->isNotEmpty() ? (int) round($validLatencies->average()) : 0;

        // Cálculo de percentil 95 (P95)
        $p95LatencyMs = 0;
        if ($validLatencies->isNotEmpty()) {
            $p95Index = (int) ceil(0.95 * $validLatencies->count()) - 1;
            $p95LatencyMs = (int) $validLatencies->get(max(0, $p95Index));
        }

        // Chequeos que cumplen con el objetivo de latencia
        $fastChecks = $logs->filter(
            fn (LatencyLog $log) => $log->latency_ms !== null && $log->latency_ms <= $targetLatencyMs
        )->count();

        $latencyComplianceRate = $totalChecks > 0 ? round(($fastChecks / $totalChecks) * 100, 2) : 100.0;

        // Presupuesto de Error (Error Budget)
        // Ejemplo: SLO = 99.00% -> Margen permitido de fallo = 1.00%
        $allowedDowntime = 100.0 - $targetAvailability;
        $actualDowntime = max(0.0, 100.0 - $uptimeSli);

        if ($allowedDowntime <= 0.0) {
            $errorBudgetPercentage = ($actualDowntime == 0.0) ? 100.0 : 0.0;
        } else {
            $consumedRatio = $actualDowntime / $allowedDowntime;
            $errorBudgetPercentage = max(0.0, round((1.0 - $consumedRatio) * 100, 2));
        }

        // Clasificación: Cumple, En riesgo, Incumple
        // 1. Incumple: SLI por debajo del SLO de disponibilidad o presupuesto agotado
        if ($uptimeSli < $targetAvailability || $errorBudgetPercentage <= 0.0) {
            $status = 'Incumple';
            $message = "El servicio no alcanza el SLO pactado ({$uptimeSli}% vs {$targetAvailability}%). Presupuesto de error consumido en su totalidad.";
        }
        // 2. En riesgo: SLI nominalmente en cumplimiento, pero presupuesto <= 25% o latencia degradada (> 85% del objetivo o compliance < 85%)
        elseif ($errorBudgetPercentage <= 25.0 || ($avgLatencyMs > 0 && $avgLatencyMs >= ($targetLatencyMs * 0.85)) || $latencyComplianceRate < 85.0) {
            $status = 'En riesgo';
            $message = "El servicio está en riesgo preventivo. Presupuesto restante: {$errorBudgetPercentage}%, Latencia promedio: {$avgLatencyMs} ms.";
        }
        // 3. Cumple
        else {
            $status = 'Cumple';
            $message = 'El servicio cumple satisfactoriamente con los objetivos de disponibilidad y rendimiento.';
        }

        return [
            'service_id' => $service->id,
            'period_days' => $periodDays,
            'slo_availability_target' => $targetAvailability,
            'slo_latency_target_ms' => $targetLatencyMs,
            'uptime_sli' => $uptimeSli,
            'avg_latency_ms' => $avgLatencyMs,
            'p95_latency_ms' => $p95LatencyMs,
            'latency_compliance_rate' => $latencyComplianceRate,
            'total_checks' => $totalChecks,
            'up_checks' => $upChecks,
            'fast_checks' => $fastChecks,
            'error_budget_percentage' => $errorBudgetPercentage,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Evalúa y sincroniza el estado de SLA en la base de datos, despachando alertas preventivas si el estado se degrada.
     */
    public function evaluateAndSyncService(Service $service, int $periodDays = 30): array
    {
        $sla = $this->calculateSla($service, $periodDays);
        $previousStatus = $service->sla_status ?? 'Cumple';
        $newStatus = $sla['status'];

        $service->updateQuietly([
            'sla_status' => $newStatus,
            'error_budget_percentage' => $sla['error_budget_percentage'],
        ]);

        // Disparar alerta si hubo degradación hacia "En riesgo" o "Incumple"
        if ($newStatus !== 'Cumple' && $previousStatus !== $newStatus) {
            $this->dispatchSlaAlert($service, $sla);
        }

        return $sla;
    }

    /**
     * Despacha notificaciones a los canales configurados (Email, Discord, Telegram).
     */
    public function dispatchSlaAlert(Service $service, array $slaData): void
    {
        $channels = NotificationChannel::query()
            ->where('user_id', $service->user_id)
            ->where('is_active', true)
            ->get();

        foreach ($channels as $channel) {
            try {
                match ($channel->type) {
                    'Email' => Mail::to($channel->target_destination)->send(new SlaAlertMail($service, $slaData)),
                    'Webhook_Discord', 'Webhook_Telegram' => $this->sendWebhookSlaAlert($channel, $service, $slaData),
                    default => null,
                };
            } catch (Throwable $e) {
                Log::error("Error despachando alerta SLA al canal [{$channel->id}]: {$e->getMessage()}");
            }
        }
    }

    /**
     * Envía payload compatible con Discord y Telegram para alertas SLA.
     */
    protected function sendWebhookSlaAlert(NotificationChannel $channel, Service $service, array $slaData): void
    {
        $icon = $slaData['status'] === 'Incumple' ? '🚨' : '⚠️';
        $title = "{$icon} Alerta de Nivel de Servicio ({$slaData['status']}) - UpTracker";
        $text = "{$title}\n"
            ."Servicio: {$service->name} ({$service->url})\n"
            ."SLI Actual: {$slaData['uptime_sli']}% (Objetivo SLO: {$slaData['slo_availability_target']}%)\n"
            ."Presupuesto de Error Restante: {$slaData['error_budget_percentage']}%\n"
            ."Latencia Promedio: {$slaData['avg_latency_ms']} ms (Objetivo: <= {$slaData['slo_latency_target_ms']} ms)\n"
            ."Detalle: {$slaData['message']}";

        $payload = [
            'event' => 'sla.alert',
            'service_name' => $service->name,
            'service_url' => $service->url,
            'sla_status' => $slaData['status'],
            'uptime_sli' => $slaData['uptime_sli'],
            'slo_target' => $slaData['slo_availability_target'],
            'error_budget_percentage' => $slaData['error_budget_percentage'],
            'content' => $text,
            'text' => $text,
            'timestamp' => now()->toISOString(),
        ];

        Http::timeout(10)->asJson()->post($channel->target_destination, $payload);
    }

    /**
     * Almacena o actualiza la fotografía diaria de cumplimiento SLA del servicio.
     */
    public function recordDailySnapshot(Service $service, ?Carbon $date = null): SlaHistory
    {
        $targetDate = $date ? $date->copy()->startOfDay() : now()->startOfDay();
        $startOfDay = $targetDate->copy()->startOfDay();
        $endOfDay = $targetDate->copy()->endOfDay();

        $logs = LatencyLog::query()
            ->where('service_id', $service->id)
            ->whereBetween('checked_at', [$startOfDay, $endOfDay])
            ->get();

        $totalChecks = $logs->count();
        $targetAvailability = (float) ($service->slo_availability_target ?? 99.00);
        $targetLatencyMs = (int) ($service->slo_latency_target_ms ?? 500);

        if ($totalChecks === 0) {
            return SlaHistory::updateOrCreate(
                ['service_id' => $service->id, 'snapshot_date' => $targetDate->toDateString()],
                [
                    'total_checks' => 0,
                    'up_checks' => 0,
                    'uptime_sli' => 100.0,
                    'avg_latency_ms' => 0,
                    'p95_latency_ms' => 0,
                    'latency_compliance_rate' => 100.0,
                    'error_budget_percentage' => 100.0,
                    'status' => 'Cumple',
                ]
            );
        }

        $upChecks = $logs->where('status', 'Up')->count();
        $uptimeSli = round(($upChecks / $totalChecks) * 100, 2);

        $latencies = $logs->whereNotNull('latency_ms')->pluck('latency_ms')->sort()->values();
        $avgLatency = $latencies->isNotEmpty() ? (int) round($latencies->average()) : 0;

        $p95Latency = 0;
        if ($latencies->isNotEmpty()) {
            $idx = (int) ceil(0.95 * $latencies->count()) - 1;
            $p95Latency = (int) $latencies->get(max(0, $idx));
        }

        $fastChecks = $logs->filter(
            fn (LatencyLog $l) => $l->latency_ms !== null && $l->latency_ms <= $targetLatencyMs
        )->count();

        $latencyCompliance = round(($fastChecks / $totalChecks) * 100, 2);

        $allowedDowntime = 100.0 - $targetAvailability;
        $actualDowntime = max(0.0, 100.0 - $uptimeSli);
        $errorBudget = ($allowedDowntime <= 0)
            ? (($actualDowntime == 0) ? 100.0 : 0.0)
            : max(0.0, round((1.0 - ($actualDowntime / $allowedDowntime)) * 100, 2));

        $status = match (true) {
            $uptimeSli < $targetAvailability || $errorBudget <= 0.0 => 'Incumple',
            $errorBudget <= 25.0 || ($avgLatency > 0 && $avgLatency >= ($targetLatencyMs * 0.85)) => 'En riesgo',
            default => 'Cumple',
        };

        return SlaHistory::updateOrCreate(
            ['service_id' => $service->id, 'snapshot_date' => $targetDate->toDateString()],
            [
                'total_checks' => $totalChecks,
                'up_checks' => $upChecks,
                'uptime_sli' => $uptimeSli,
                'avg_latency_ms' => $avgLatency,
                'p95_latency_ms' => $p95Latency,
                'latency_compliance_rate' => $latencyCompliance,
                'error_budget_percentage' => $errorBudget,
                'status' => $status,
            ]
        );
    }

    /**
     * Retorna el histórico de SLA por días para gráficos y tablas de auditoría.
     *
     * @return Collection<int, array{date: string, uptime_sli: float, error_budget: float, avg_latency: int, status: string}>
     */
    public function getHistoricalTrend(Service $service, int $days = 7): Collection
    {
        $result = collect();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $dateStr = $date->toDateString();

            $history = SlaHistory::query()
                ->where('service_id', $service->id)
                ->where('snapshot_date', $dateStr)
                ->first();

            if (! $history) {
                // Generar snapshot dinámicamente si no existía
                $history = $this->recordDailySnapshot($service, $date);
            }

            $result->push([
                'date' => $date->format('d/m'),
                'full_date' => $dateStr,
                'uptime_sli' => (float) $history->uptime_sli,
                'error_budget' => (float) $history->error_budget_percentage,
                'avg_latency' => (int) $history->avg_latency_ms,
                'status' => $history->status,
            ]);
        }

        return $result;
    }
}
