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
            ->with(['latencyLogs' => fn($q) =>$q->latest()->limit(10)])
            ->latest()
            ->get();

        // Métricas para las tarjetas de resumen
        $totalServices =$services->count();
        $onlineServices =$services->where('status', 'online')->count();
        $offlineServices =$services->where('status', 'offline')->count();

        return view('services.index', compact('services', 'totalServices', 'onlineServices', 'offlineServices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:255',
            'check_interval' => 'required|integer|in:30,60,300,600,900,1800,3600',
        ]);

        auth()->user()->services()->create($validated);

        return redirect()->route('services.index')->with('success', 'Endpoint registrado correctamente.');
    }

    public function destroy(Service $service)
    {
        // Seguridad: solo el dueño puede borrarlo
        abort_if($service->user_id !== auth()->id(), 403);

        $service->delete();

        return redirect()->route('services.index')->with('success', 'Servicio eliminado.');
    }
}
