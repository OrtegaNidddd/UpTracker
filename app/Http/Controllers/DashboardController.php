<?php

namespace App\Http\Controllers;

use App\Models\LatencyLog;
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

        // 3. Procesar métricas e histórico de 50 registros por servicio para Chart.js
        foreach ($services as $service) {
            $stats = $uptimeStats->get($service->id);
            $totalChecks = $stats ? (int) $stats->total_checks : 0;
            $upChecks = $stats ? (int) $stats->up_checks : 0;

            $service->uptime_24h = $totalChecks > 0
                ? round(($upChecks / $totalChecks) * 100, 2)
                : 100.0;

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

        return view('dashboard', compact('services'));
    }
}
