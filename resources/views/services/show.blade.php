<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('services.index') }}" class="text-slate-400 hover:text-slate-600 transition">
                    &larr; Volver
                </a>
                <h2 class="font-bold text-xl text-brand-dark leading-tight flex items-center gap-2">
                    {{ $service->name }}
                </h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $service->status === 'online' ? 'bg-brand-mint/15 text-brand-mint' : ($service->status === 'degraded' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                    {{ ucfirst($service->status) }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase {{ $sla['status'] === 'Cumple' ? 'bg-emerald-100 text-emerald-800' : ($sla['status'] === 'En riesgo' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                    SLA: {{ $sla['status'] }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button 
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'edit-service-modal')"
                    class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold rounded-lg shadow-sm transition">
                    ✏️ Configurar SLOs & Endpoint
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-brand-ice min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Mensajes Flash -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 text-sm font-medium rounded-xl">
                    {{ session('success') }}
                </div>
            @endif

            <!-- 1. Tarjetas Resumen de Nivel de Servicio (SLA / SLO / Error Budget) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">SLI Disponibilidad (30d)</span>
                    <p class="text-3xl font-black text-brand-dark mt-1">{{ $sla['uptime_sli'] }}%</p>
                    <span class="text-xs text-slate-400">Meta pactada (SLO): {{ $sla['slo_availability_target'] }}%</span>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Presupuesto de Error</span>
                    <p class="text-3xl font-black {{ $sla['error_budget_percentage'] <= 0 ? 'text-rose-600' : ($sla['error_budget_percentage'] <= 25 ? 'text-amber-600' : 'text-emerald-600') }} mt-1">
                        {{ $sla['error_budget_percentage'] }}%
                    </p>
                    <span class="text-xs text-slate-400">Margen de fallo antes de penalización</span>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Latencia Promedio / P95</span>
                    <p class="text-3xl font-black text-slate-800 mt-1">{{ $sla['avg_latency_ms'] }} <span class="text-sm font-normal text-slate-400">ms</span></p>
                    <span class="text-xs text-slate-400">P95: {{ $sla['p95_latency_ms'] }} ms | Meta: &le; {{ $sla['slo_latency_target_ms'] }} ms</span>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Cumplimiento de Latencia</span>
                    <p class="text-3xl font-black text-brand-primary mt-1">{{ $sla['latency_compliance_rate'] }}%</p>
                    <span class="text-xs text-slate-400">{{ $sla['fast_checks'] }} de {{ $sla['total_checks'] }} mediciones</span>
                </div>
            </div>

            <!-- 2. Información del Endpoint & Diagnóstico SLA -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-base text-slate-800">Diagnóstico del Nivel de Servicio</h3>
                    <span class="font-mono text-xs text-slate-500">{{ $service->url }}</span>
                </div>

                <div class="p-4 rounded-xl {{ $sla['status'] === 'Cumple' ? 'bg-emerald-50 border border-emerald-200 text-emerald-900' : ($sla['status'] === 'En riesgo' ? 'bg-amber-50 border border-amber-200 text-amber-900' : 'bg-rose-50 border border-rose-200 text-rose-900') }}">
                    <div class="flex items-center gap-2 font-bold text-sm">
                        <span>{{ $sla['status'] === 'Cumple' ? '✅' : ($sla['status'] === 'En riesgo' ? '⚠️' : '🚨') }}</span>
                        <span>Estado: {{ $sla['status'] }}</span>
                    </div>
                    <p class="text-xs mt-1.5 opacity-90">{{ $sla['message'] }}</p>
                </div>

                <!-- Barra de Presupuesto de Error -->
                <div class="space-y-1.5 pt-2">
                    <div class="flex justify-between text-xs font-medium text-slate-600">
                        <span>Consumo de Presupuesto de Error</span>
                        <span>{{ $sla['error_budget_percentage'] }}% Disponible</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div 
                            class="h-full rounded-full transition-all duration-500 {{ $sla['error_budget_percentage'] <= 0 ? 'bg-rose-500' : ($sla['error_budget_percentage'] <= 25 ? 'bg-amber-500' : 'bg-emerald-500') }}" 
                            style="width: {{ min(100, max(0, $sla['error_budget_percentage'])) }}%;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Tabla Histórica Diaria de SLA (Auditoría Continua) -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-base text-slate-800">Bitácora Histórica de SLA (Snapshots Diarios)</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Registro de cumplimiento diario para auditoría de niveles de servicio.</p>
                    </div>
                    <span class="text-xs text-slate-400">Total registros: {{ $service->slaHistories->count() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                            <tr>
                                <th class="p-3.5">Fecha</th>
                                <th class="p-3.5">Chequeos Totales</th>
                                <th class="p-3.5">Chequeos Exitosos</th>
                                <th class="p-3.5">SLI Disponibilidad</th>
                                <th class="p-3.5">Latencia Promedio</th>
                                <th class="p-3.5">Latencia P95</th>
                                <th class="p-3.5">Cumplimiento Latencia</th>
                                <th class="p-3.5">Error Budget</th>
                                <th class="p-3.5">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($service->slaHistories as $history)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3.5 font-bold text-slate-800">{{ $history->snapshot_date->format('Y-m-d') }}</td>
                                    <td class="p-3.5">{{ $history->total_checks }}</td>
                                    <td class="p-3.5">{{ $history->up_checks }}</td>
                                    <td class="p-3.5 font-bold">{{ $history->uptime_sli }}%</td>
                                    <td class="p-3.5">{{ $history->avg_latency_ms }} ms</td>
                                    <td class="p-3.5">{{ $history->p95_latency_ms }} ms</td>
                                    <td class="p-3.5">{{ $history->latency_compliance_rate }}%</td>
                                    <td class="p-3.5 font-bold {{ $history->error_budget_percentage <= 25 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $history->error_budget_percentage }}%
                                    </td>
                                    <td class="p-3.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $history->status === 'Cumple' ? 'bg-emerald-100 text-emerald-800' : ($history->status === 'En riesgo' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                            {{ $history->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-6 text-center text-slate-400">
                                        Aún no hay fotografías históricas registradas para este endpoint.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Bitácora de Incidentes del Endpoint -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <h3 class="font-bold text-base text-slate-800">Incidentes Registrados en este Endpoint</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                            <tr>
                                <th class="p-3.5">Tipo</th>
                                <th class="p-3.5">Inicio</th>
                                <th class="p-3.5">Resolución</th>
                                <th class="p-3.5">Duración</th>
                                <th class="p-3.5">Detalles</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($service->incidents as $incident)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded font-bold uppercase {{ $incident->incident_type === 'Down' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $incident->incident_type }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 font-mono text-slate-500">{{ $incident->started_at ? $incident->started_at->format('Y-m-d H:i:s') : 'N/D' }}</td>
                                    <td class="p-3.5 font-mono text-slate-500">{{ $incident->resolved_at ? $incident->resolved_at->format('Y-m-d H:i:s') : 'Activo' }}</td>
                                    <td class="p-3.5">{{ $incident->duration_seconds ? "{$incident->duration_seconds} seg" : 'En curso' }}</td>
                                    <td class="p-3.5 font-mono text-[11px] text-slate-600">{{ $incident->details }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-slate-400">
                                        No se han registrado incidentes en este endpoint.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Editar Servicio y Objetivos SLA -->
    <x-modal name="edit-service-modal" :show="$errors->isNotEmpty()" focusable>
        <form method="POST" action="{{ route('services.update', $service) }}" class="p-6">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-bold text-brand-dark">
                Editar Configuración y Objetivos SLA/SLO
            </h2>
            <p class="mt-1 text-sm text-brand-muted">
                Modifica los umbrales de disponibilidad y latencia para este servicio.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="edit_name" value="Nombre del Servicio" class="text-brand-slate" />
                    <x-text-input id="edit_name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $service->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="edit_url" value="URL del Endpoint" class="text-brand-slate" />
                    <x-text-input id="edit_url" name="url" type="url" class="mt-1 block w-full" :value="old('url', $service->url)" required />
                    <x-input-error :messages="$errors->get('url')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="edit_check_interval" value="Intervalo de Chequeo" class="text-brand-slate" />
                        <select id="edit_check_interval" name="check_interval" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                            <option value="30" @selected(old('check_interval', $service->interval_seconds) == 30)>Cada 30 segundos</option>
                            <option value="60" @selected(old('check_interval', $service->interval_seconds) == 60)>Cada 1 minuto</option>
                            <option value="300" @selected(old('check_interval', $service->interval_seconds) == 300)>Cada 5 minutos</option>
                            <option value="600" @selected(old('check_interval', $service->interval_seconds) == 600)>Cada 10 minutos</option>
                            <option value="900" @selected(old('check_interval', $service->interval_seconds) == 900)>Cada 15 minutos</option>
                            <option value="1800" @selected(old('check_interval', $service->interval_seconds) == 1800)>Cada 30 minutos</option>
                            <option value="3600" @selected(old('check_interval', $service->interval_seconds) == 3600)>Cada 1 hora</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="edit_http_method" value="Método HTTP" class="text-brand-slate" />
                        <select id="edit_http_method" name="http_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="GET" @selected(strtoupper($service->http_method) === 'GET')>GET</option>
                            <option value="HEAD" @selected(strtoupper($service->http_method) === 'HEAD')>HEAD</option>
                        </select>
                    </div>
                </div>

                <!-- Objetivos SLA/SLO -->
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Objetivos de Nivel de Servicio (SLO)</h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="edit_slo_availability_target" value="SLO Disponibilidad (%)" class="text-brand-slate text-xs" />
                            <x-text-input id="edit_slo_availability_target" name="slo_availability_target" type="number" step="0.01" min="50" max="100" class="mt-1 block w-full text-xs" :value="old('slo_availability_target', $service->slo_availability_target ?? '99.00')" required />
                            <x-input-error :messages="$errors->get('slo_availability_target')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="edit_slo_latency_target_ms" value="SLO Latencia Máx (ms)" class="text-brand-slate text-xs" />
                            <x-text-input id="edit_slo_latency_target_ms" name="slo_latency_target_ms" type="number" min="10" max="30000" class="mt-1 block w-full text-xs" :value="old('slo_latency_target_ms', $service->slo_latency_target_ms ?? '500')" required />
                            <x-input-error :messages="$errors->get('slo_latency_target_ms')" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-primary-button class="bg-brand-primary">
                    Guardar Cambios
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
