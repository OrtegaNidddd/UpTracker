<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\LatencyLog;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class PublicStatusController extends Controller
{
    /**
     * Retorna el estado público consolidado de todos los servicios activos del sistema.
     */
    public function __invoke(): JsonResponse
    {
        $services = Service::query()
            ->where('is_active', true)
            ->with(['openIncident'])
            ->get();

        if ($services->isEmpty()) {
            return response()->json([
                'status' => 'No_Services',
                'summary' => 'No hay servicios públicos registrados actualmente.',
                'services' => [],
                'active_incidents' => [],
                'resolved_incidents' => [],
                'generated_at' => now()->toISOString(),
            ]);
        }

        $serviceIds = $services->pluck('id');
        $twentyFourHoursAgo = now()->subHours(24);

        $uptimeStats = LatencyLog::query()
            ->whereIn('service_id', $serviceIds)
            ->where('checked_at', '>=', $twentyFourHoursAgo)
            ->selectRaw('service_id, COUNT(*) as total_checks, SUM(CASE WHEN status = "Up" THEN 1 ELSE 0 END) as up_checks')
            ->groupBy('service_id')
            ->get()
            ->keyBy('service_id');

        $activeServicesData = [];
        $offlineCount = 0;
        $degradedCount = 0;

        foreach ($services as $service) {
            $stats = $uptimeStats->get($service->id);
            $totalChecks = $stats ? (int) $stats->total_checks : 0;
            $upChecks = $stats ? (int) $stats->up_checks : 0;

            $uptime = $totalChecks > 0
                ? round(($upChecks / $totalChecks) * 100, 2)
                : 100.0;

            $status = $service->status;
            if ($status === 'offline') {
                $offlineCount++;
            } elseif ($status === 'degraded') {
                $degradedCount++;
            }

            $activeServicesData[] = [
                'id' => $service->id,
                'name' => $service->name,
                'status' => $status,
                'uptime_24h' => $uptime,
                'ssl_status' => $service->ssl_status ?? 'None',
                'last_checked_at' => $service->last_checked_at?->toISOString(),
            ];
        }

        $totalCount = $services->count();
        $systemStatus = match (true) {
            $offlineCount === 0 && $degradedCount === 0 => 'Operational',
            $offlineCount > 0 && ($offlineCount / $totalCount) >= 0.5 => 'Major_Outage',
            $offlineCount > 0 => 'Partial_Outage',
            default => 'Degraded_Performance',
        };

        $activeIncidents = Incident::query()
            ->whereIn('service_id', $serviceIds)
            ->whereNull('resolved_at')
            ->with('service:id,name')
            ->latest('started_at')
            ->get()
            ->map(fn (Incident $incident) => [
                'id' => $incident->id,
                'service_name' => $incident->service->name,
                'incident_type' => $incident->incident_type,
                'started_at' => $incident->started_at?->toISOString(),
                'details' => $incident->details,
            ]);

        $resolvedIncidents = Incident::query()
            ->whereIn('service_id', $serviceIds)
            ->whereNotNull('resolved_at')
            ->with('service:id,name')
            ->latest('resolved_at')
            ->limit(5)
            ->get()
            ->map(fn (Incident $incident) => [
                'id' => $incident->id,
                'service_name' => $incident->service->name,
                'incident_type' => $incident->incident_type,
                'started_at' => $incident->started_at?->toISOString(),
                'resolved_at' => $incident->resolved_at?->toISOString(),
                'duration_seconds' => $incident->duration_seconds,
            ]);

        return response()->json([
            'status' => $systemStatus,
            'summary' => match ($systemStatus) {
                'Operational' => 'Todos los sistemas operando con normalidad.',
                'Major_Outage' => 'Caída crítica en múltiples servicios.',
                'Partial_Outage' => 'Interrupción parcial en algunos servicios.',
                default => 'Degradación de rendimiento detectada.',
            },
            'total_monitored' => $totalCount,
            'online_count' => $totalCount - $offlineCount - $degradedCount,
            'offline_count' => $offlineCount,
            'degraded_count' => $degradedCount,
            'services' => $activeServicesData,
            'active_incidents' => $activeIncidents,
            'resolved_incidents' => $resolvedIncidents,
            'generated_at' => now()->toISOString(),
        ]);
    }
}
