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
        $targetAvailability = (float) ($service->slo_availability_target ?? 99.00);
        $targetLatencyMs = (int) ($service->slo_latency_target_ms ?? 500);

        // Agregación directa en SQL para máxima velocidad y evitar desbordamiento de memoria
        $stats = LatencyLog::query()
            ->where('service_id', $service->id)
            ->where('checked_at', '>=', $since)
            ->selectRaw("
                COUNT(*) as total_checks,
                SUM(CASE WHEN status = 'Up' THEN 1 ELSE 0 END) as up_checks,
                SUM(CASE WHEN latency_ms IS NOT NULL AND latency_ms <= {$targetLatencyMs} THEN 1 ELSE 0 END) as fast_checks,
                AVG(CASE WHEN latency_ms IS NOT NULL THEN latency_ms END) as avg_latency
            ")
            ->first();

        $totalChecks = $stats ? (int) $stats->total_checks : 0;

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

        $upChecks = (int) ($stats->up_checks ?? 0);
        $fastChecks = (int) ($stats->fast_checks ?? 0);
        $avgLatencyMs = $stats->avg_latency !== null ? (int) round((float) $stats->avg_latency) : 0;
        $uptimeSli = round(($upChecks / $totalChecks) * 100, 2);
        $latencyComplianceRate = round(($fastChecks / $totalChecks) * 100, 2);

        // Cálculo de percentil 95 (P95) acotado a muestra reciente de hasta 1000 registros
        $p95LatencyMs = 0;
        $validLatencies = LatencyLog::query()
            ->where('service_id', $service->id)
            ->where('checked_at', '>=', $since)
            ->whereNotNull('latency_ms')
            ->latest('checked_at')
            ->limit(1000)
            ->pluck('latency_ms')
            ->sort()
            ->values();

        if ($validLatencies->isNotEmpty()) {
            $p95Index = (int) ceil(0.95 * $validLatencies->count()) - 1;
            $p95LatencyMs = (int) $validLatencies->get(max(0, $p95Index));
        }

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
     * Construye las métricas de snapshot diario de un servicio en memoria sin bloquear la BD con escrituras innecesarias.
     *
     * @return array{
     *     total_checks: int,
     *     up_checks: int,
     *     uptime_sli: float,
     *     avg_latency_ms: int,
     *     p95_latency_ms: int,
     *     latency_compliance_rate: float,
     *     error_budget_percentage: float,
     *     status: string
     * }
     */
    public function buildDailySnapshotData(Service $service, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        $targetAvailability = (float) ($service->slo_availability_target ?? 99.00);
        $targetLatencyMs = (int) ($service->slo_latency_target_ms ?? 500);

        $stats = LatencyLog::query()
            ->where('service_id', $service->id)
            ->whereBetween('checked_at', [$startOfDay, $endOfDay])
            ->selectRaw("
                COUNT(*) as total_checks,
                SUM(CASE WHEN status = 'Up' THEN 1 ELSE 0 END) as up_checks,
                SUM(CASE WHEN latency_ms IS NOT NULL AND latency_ms <= {$targetLatencyMs} THEN 1 ELSE 0 END) as fast_checks,
                AVG(CASE WHEN latency_ms IS NOT NULL THEN latency_ms END) as avg_latency
            ")
            ->first();

        $totalChecks = $stats ? (int) $stats->total_checks : 0;

        if ($totalChecks === 0) {
            return [
                'total_checks' => 0,
                'up_checks' => 0,
                'uptime_sli' => 100.0,
                'avg_latency_ms' => 0,
                'p95_latency_ms' => 0,
                'latency_compliance_rate' => 100.0,
                'error_budget_percentage' => 100.0,
                'status' => 'Cumple',
            ];
        }

        $upChecks = (int) ($stats->up_checks ?? 0);
        $fastChecks = (int) ($stats->fast_checks ?? 0);
        $avgLatency = $stats->avg_latency !== null ? (int) round((float) $stats->avg_latency) : 0;
        $uptimeSli = round(($upChecks / $totalChecks) * 100, 2);
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

        return [
            'total_checks' => $totalChecks,
            'up_checks' => $upChecks,
            'uptime_sli' => $uptimeSli,
            'avg_latency_ms' => $avgLatency,
            'p95_latency_ms' => 0,
            'latency_compliance_rate' => $latencyCompliance,
            'error_budget_percentage' => $errorBudget,
            'status' => $status,
        ];
    }

    /**
     * Almacena o actualiza la fotografía diaria de cumplimiento SLA del servicio.
     */
    public function recordDailySnapshot(Service $service, ?Carbon $date = null): SlaHistory
    {
        $targetDate = $date ? $date->copy()->startOfDay() : now()->startOfDay();
        $data = $this->buildDailySnapshotData($service, $targetDate);

        return SlaHistory::updateOrCreate(
            ['service_id' => $service->id, 'snapshot_date' => $targetDate->toDateString()],
            $data
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
        $startDate = now()->subDays($days - 1)->startOfDay();

        // Carga en lote de snapshots existentes para evitar bloqueos y el problema N+1
        $existingHistories = SlaHistory::query()
            ->where('service_id', $service->id)
            ->where('snapshot_date', '>=', $startDate->toDateString())
            ->get()
            ->keyBy(fn (SlaHistory $h) => Carbon::parse($h->snapshot_date)->toDateString());

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $dateStr = $date->toDateString();

            $history = $existingHistories->get($dateStr);

            if (! $history) {
                // Durante consultas web GET, calculamos en memoria para no saturar SQLite con escrituras concurrentes
                $data = $this->buildDailySnapshotData($service, $date);
                $uptimeSli = $data['uptime_sli'];
                $errorBudget = $data['error_budget_percentage'];
                $avgLatency = $data['avg_latency_ms'];
                $status = $data['status'];
            } else {
                $uptimeSli = (float) $history->uptime_sli;
                $errorBudget = (float) $history->error_budget_percentage;
                $avgLatency = (int) $history->avg_latency_ms;
                $status = $history->status;
            }

            $result->push([
                'date' => $date->format('d/m'),
                'full_date' => $dateStr,
                'uptime_sli' => $uptimeSli,
                'error_budget' => $errorBudget,
                'avg_latency' => $avgLatency,
                'status' => $status,
            ]);
        }

        return $result;
    }
}
