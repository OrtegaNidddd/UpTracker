<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    public function index()
    {
        // Trae los servicios del usuario con su último log de latencia registrado
        $services = auth()->user()->services()
            ->with(['latencyLogs' => fn ($q) => $q->latest('checked_at')->limit(10)])
            ->latest()
            ->get();

        // Métricas para las tarjetas de resumen
        $totalServices = $services->count();
        $onlineServices = $services->where('status', 'online')->count();
        $offlineServices = $services->where('status', 'offline')->count();

        return view('services.index', compact('services', 'totalServices', 'onlineServices', 'offlineServices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'url:http,https', 'max:2048'],
            'check_interval' => ['required', 'integer', 'in:30,60,300,600,900,1800,3600'],
            'latency_threshold_ms' => ['nullable', 'integer', 'min:50', 'max:60000'],
            'slo_availability_target' => ['nullable', 'numeric', 'min:50', 'max:100'],
            'slo_latency_target_ms' => ['nullable', 'integer', 'min:10', 'max:30000'],
            'http_method' => ['nullable', 'string', 'in:GET,HEAD,get,head'],
        ]);

        if (isset($validated['http_method'])) {
            $validated['http_method'] = strtoupper($validated['http_method']);
        } else {
            $validated['http_method'] = 'GET';
        }

        auth()->user()->services()->create($validated);

        return redirect()->route('services.index')->with('success', 'Endpoint registrado correctamente con sus objetivos SLO.');
    }

    public function show(Service $service)
    {
        abort_if((int) $service->user_id !== (int) auth()->id(), 403);

        // Combinamos la carga de relaciones de ambas ramas (logs, incidentes, historial de SLA)
        $service->load([
            'latencyLogs' => fn ($q) => $q->latest('checked_at')->limit(50),
            'incidents' => fn ($q) => $q->latest('started_at')->limit(15),
            'slaHistories' => fn ($q) => $q->latest('snapshot_date')->limit(30),
        ]);

        $slaService = app(SlaService::class);
        try {
            $sla = $slaService->calculateSla($service, 30);
            $slaTrend = $slaService->getHistoricalTrend($service, 7);
        } catch (\Throwable $e) {
            Log::error("Error calculando SLA en service {$service->id}: {$e->getMessage()}");
            $sla = [
                'service_id' => $service->id,
                'period_days' => 30,
                'slo_availability_target' => (float) ($service->slo_availability_target ?? 99.00),
                'slo_latency_target_ms' => (int) ($service->slo_latency_target_ms ?? 500),
                'uptime_sli' => 100.0,
                'avg_latency_ms' => 0,
                'p95_latency_ms' => 0,
                'latency_compliance_rate' => 100.0,
                'total_checks' => 0,
                'up_checks' => 0,
                'fast_checks' => 0,
                'error_budget_percentage' => 100.0,
                'status' => 'Cumple',
                'message' => 'El cálculo de SLA se encuentra temporalmente en modo contingencia.',
            ];
            $slaTrend = collect();
        }

        // Métricas de uptime de 24 horas y datos para gráficos de la vista
        $twentyFourHoursAgo = now()->subHours(24);
        $totalChecks = $service->latencyLogs()->where('checked_at', '>=', $twentyFourHoursAgo)->count();
        $upChecks = $service->latencyLogs()->where('checked_at', '>=', $twentyFourHoursAgo)->where('status', 'Up')->count();
        $service->uptime_24h = $totalChecks > 0 ? round(($upChecks / $totalChecks) * 100, 2) : 100.0;

        $chronologicalLogs = $service->latencyLogs->reverse()->values();
        $chartLabels = $chronologicalLogs->map(fn ($log) => $log->checked_at?->format('H:i:s') ?? '')->all();
        $chartData = $chronologicalLogs->map(fn ($log) => $log->latency_ms ?? 0)->all();

        if (request()->wantsJson()) {
            return response()->json([
                'service' => $service,
                'sla' => $sla,
                'sla_trend' => $slaTrend,
                'chart_labels' => $chartLabels,
                'chart_data' => $chartData,
            ]);
        }

        return view('services.show', compact('service', 'sla', 'slaTrend', 'chartLabels', 'chartData'));
    }

    public function update(Request $request, Service $service)
    {
        abort_if((int) $service->user_id !== (int) auth()->id(), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'url' => ['sometimes', 'required', 'string', 'url:http,https', 'max:2048'],
            'check_interval' => ['sometimes', 'required', 'integer', 'in:30,60,300,600,900,1800,3600'],
            'latency_threshold_ms' => ['nullable', 'integer', 'min:50', 'max:60000'],
            'slo_availability_target' => ['nullable', 'numeric', 'min:50', 'max:100'],
            'slo_latency_target_ms' => ['nullable', 'integer', 'min:10', 'max:30000'],
            'http_method' => ['nullable', 'string', 'in:GET,HEAD,get,head'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['http_method'])) {
            $validated['http_method'] = strtoupper($validated['http_method']);
        }

        $service->update($validated);

        // Recalcular SLA tras actualización de objetivos
        app(SlaService::class)->evaluateAndSyncService($service);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Servicio actualizado.', 'service' => $service]);
        }

        return redirect()->route('services.index')->with('success', 'Servicio y objetivos SLO actualizados correctamente.');
    }

    public function destroy(Service $service)
    {
        // Seguridad: solo el dueño puede borrarlo
        abort_if((int) $service->user_id !== (int) auth()->id(), 403);

        $service->delete();

        return redirect()->route('services.index')->with('success', 'Servicio eliminado.');
    }
}
