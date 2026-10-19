<?php

namespace App\Http\Controllers;

use App\Models\LatencyLog;
use App\Services\SlaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Muestra el panel principal con las métricas e histórico de cada servicio.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Aislamiento multiusuario estricto y precarga de incidentes abiertos
        $services = $user->services()
            ->with(['openIncident'])
            ->latest()
            ->get();

        if ($services->isEmpty()) {
            return view('dashboard', [
                'services' => $services,
            ]);
        }

        $serviceIds = $services->pluck('id');
        $twentyFourHoursAgo = now()->subHours(24);

        // 2. Consulta agregada por lotes para calcular el uptime de 24h sin problema N+1
        $uptimeStats = LatencyLog::query()
            ->whereIn('service_id', $serviceIds)
            ->where('checked_at', '>=', $twentyFourHoursAgo)
            ->selectRaw('service_id, COUNT(*) as total_checks, SUM(CASE WHEN status = "Up" THEN 1 ELSE 0 END) as up_checks')
            ->groupBy('service_id')
            ->get()
            ->keyBy('service_id');

        $slaService = app(SlaService::class);
        $slaCompliantCount = 0;
        $slaAtRiskCount = 0;
        $slaBreachedCount = 0;

        // 3. Procesar métricas e histórico de 50 registros por servicio para Chart.js y SLA
        foreach ($services as $service) {
            $stats = $uptimeStats->get($service->id);
            $totalChecks = $stats ? (int) $stats->total_checks : 0;
            $upChecks = $stats ? (int) $stats->up_checks : 0;

            $service->uptime_24h = $totalChecks > 0
                ? round(($upChecks / $totalChecks) * 100, 2)
                : 100.0;

            // Análisis y cálculo SLA/SLO (30 días)
            $sla = $slaService->calculateSla($service, 30);
            $service->sla = $sla;
            $service->sla_trend = $slaService->getHistoricalTrend($service, 7);

            match ($sla['status']) {
                'Cumple' => $slaCompliantCount++,
                'En riesgo' => $slaAtRiskCount++,
                default => $slaBreachedCount++,
            };

            // Obtener los 50 logs más recientes ordenados cronológicamente (antiguo -> reciente)
            $recentLogs = $service->latencyLogs()
                ->latest('checked_at')
                ->limit(50)
                ->get()
                ->reverse()
                ->values();

            $service->latency_history = $recentLogs;

            // Datos formateados listos para Chart.js
            $service->chart_labels = $recentLogs->map(
                fn (LatencyLog $log) => $log->checked_at?->format('H:i:s') ?? ''
            )->all();

            $service->chart_data = $recentLogs->map(
                fn (LatencyLog $log) => $log->latency_ms
            )->all();
        }

        $slaSummary = [
            'compliant' => $slaCompliantCount,
            'at_risk' => $slaAtRiskCount,
            'breached' => $slaBreachedCount,
            'overall_rate' => $services->count() > 0 ? round(($slaCompliantCount / $services->count()) * 100, 1) : 100.0,
        ];

        return view('dashboard', compact('services', 'slaSummary'));
    }
}
