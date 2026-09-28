<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncidentResource;
use App\Http\Resources\LatencyLogResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ServiceApiController extends Controller
{
    /**
     * Muestra la lista de servicios monitoreados pertenecientes al usuario autenticado.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $services = $request->user()->services()
            ->with([
                'latencyLogs' => fn ($query) => $query->latest('checked_at')->limit(10),
                'incidents' => fn ($query) => $query->latest('started_at')->limit(5),
            ])
            ->latest('id')
            ->get();

        return ServiceResource::collection($services);
    }

    /**
     * Registra un nuevo servicio/URL a monitorear con validación estricta de esquemas HTTP/HTTPS.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'url:http,https', 'max:2048'],
            'http_method' => ['nullable', 'string', Rule::in(['GET', 'HEAD', 'get', 'head'])],
            'custom_headers' => ['nullable', 'array'],
            'custom_headers.*' => ['string', 'max:500'],
            'interval_seconds' => ['required', 'integer', 'in:10,15,20,30,60,120,300,600,900,1800,3600'],
            'latency_threshold_ms' => ['nullable', 'integer', 'min:50', 'max:60000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['http_method'])) {
            $validated['http_method'] = strtoupper($validated['http_method']);
        } else {
            $validated['http_method'] = 'GET';
        }

        $service = $request->user()->services()->create($validated);

        return (new ServiceResource($service))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Consulta el detalle y métricas de un servicio individual asegurando aislamiento de datos.
     */
    public function show(Request $request, Service $service): ServiceResource
    {
        $this->authorizeServiceOwner($request, $service);

        $service->load([
            'latencyLogs' => fn ($query) => $query->latest('checked_at')->limit(50),
            'incidents' => fn ($query) => $query->latest('started_at')->limit(20),
        ]);

        return new ServiceResource($service);
    }

    /**
     * Actualiza la configuración, umbrales e intervalos de un servicio existente.
     */
    public function update(Request $request, Service $service): ServiceResource
    {
        $this->authorizeServiceOwner($request, $service);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'url' => ['sometimes', 'required', 'string', 'url:http,https', 'max:2048'],
            'http_method' => ['nullable', 'string', Rule::in(['GET', 'HEAD', 'get', 'head'])],
            'custom_headers' => ['nullable', 'array'],
            'custom_headers.*' => ['string', 'max:500'],
            'interval_seconds' => ['sometimes', 'required', 'integer', 'in:10,15,20,30,60,120,300,600,900,1800,3600'],
            'latency_threshold_ms' => ['nullable', 'integer', 'min:50', 'max:60000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['http_method'])) {
            $validated['http_method'] = strtoupper($validated['http_method']);
        }

        $service->update($validated);

        return new ServiceResource($service);
    }

    /**
     * Da de baja y elimina un endpoint de monitoreo.
     */
    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->authorizeServiceOwner($request, $service);

        $service->delete();

        return response()->json([
            'message' => 'Servicio eliminado correctamente.',
        ], 200);
    }

    /**
     * Devuelve el histórico de logs de latencia para el servicio consultado.
     */
    public function logs(Request $request, Service $service): AnonymousResourceCollection
    {
        $this->authorizeServiceOwner($request, $service);

        $logs = $service->latencyLogs()
            ->latest('checked_at')
            ->paginate($request->integer('per_page', 50));

        return LatencyLogResource::collection($logs);
    }

    /**
     * Devuelve el histórico de incidentes para el servicio consultado.
     */
    public function incidents(Request $request, Service $service): AnonymousResourceCollection
    {
        $this->authorizeServiceOwner($request, $service);

        $incidents = $service->incidents()
            ->latest('started_at')
            ->paginate($request->integer('per_page', 20));

        return IncidentResource::collection($incidents);
    }

    /**
     * Valida que el servicio pertenezca al usuario autenticado (multi-tenancy a nivel lógico).
     */
    protected function authorizeServiceOwner(Request $request, Service $service): void
    {
        abort_if((int) $service->user_id !== (int) $request->user()->id, 403, 'Acceso denegado a este servicio.');
    }
}
