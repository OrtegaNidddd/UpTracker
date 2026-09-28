<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

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
            'http_method' => ['nullable', 'string', 'in:GET,HEAD,get,head'],
        ]);

        if (isset($validated['http_method'])) {
            $validated['http_method'] = strtoupper($validated['http_method']);
        } else {
            $validated['http_method'] = 'GET';
        }

        auth()->user()->services()->create($validated);

        return redirect()->route('services.index')->with('success', 'Endpoint registrado correctamente.');
    }

    public function show(Service $service)
    {
        abort_if((int) $service->user_id !== (int) auth()->id(), 403);

        $service->load([
            'latencyLogs' => fn ($q) => $q->latest('checked_at')->limit(30),
            'incidents' => fn ($q) => $q->latest('started_at')->limit(10),
        ]);

        if (request()->wantsJson()) {
            return response()->json($service);
        }

        return view('services.show', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        abort_if((int) $service->user_id !== (int) auth()->id(), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'url' => ['sometimes', 'required', 'string', 'url:http,https', 'max:2048'],
            'check_interval' => ['sometimes', 'required', 'integer', 'in:30,60,300,600,900,1800,3600'],
            'latency_threshold_ms' => ['nullable', 'integer', 'min:50', 'max:60000'],
            'http_method' => ['nullable', 'string', 'in:GET,HEAD,get,head'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['http_method'])) {
            $validated['http_method'] = strtoupper($validated['http_method']);
        }

        $service->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Servicio actualizado.', 'service' => $service]);
        }

        return redirect()->route('services.index')->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy(Service $service)
    {
        // Seguridad: solo el dueño puede borrarlo
        abort_if((int) $service->user_id !== (int) auth()->id(), 403);

        $service->delete();

        return redirect()->route('services.index')->with('success', 'Servicio eliminado.');
    }
}
