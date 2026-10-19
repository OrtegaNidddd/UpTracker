<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-brand-dark leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Panel de Métricas y Monitoreo en Tiempo Real
            </h2>

            <a href="{{ route('services.index') }}" class="inline-flex items-center px-4 py-2 bg-brand-primary hover:opacity-90 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                Administrar Endpoints
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-brand-ice min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- 1. Tarjetas de Resumen General -->
            @php
                $totalServices = $services->count();
                $avgUptime = $totalServices > 0 ? round($services->avg('uptime_24h'), 2) : 100.0;
                $activeIncidentsCount = $services->filter(fn ($s) => $s->openIncident !== null)->count();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-muted">Servicios Monitoreados</span>
                    <p class="text-3xl font-extrabold text-brand-dark mt-2">{{ $totalServices }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-mint">Disponibilidad Promedio (24h)</span>
                    <p class="text-3xl font-extrabold text-brand-mint mt-2">{{ $avgUptime }}%</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold uppercase tracking-wider text-rose-500">Incidentes Activos</span>
                    <p class="text-3xl font-extrabold {{ $activeIncidentsCount > 0 ? 'text-rose-600' : 'text-slate-700' }} mt-2">
                        {{ $activeIncidentsCount }}
                    </p>
                </div>
            </div>

            <!-- 1.1 Tarjetas de Gestión y Análisis de Niveles de Servicio (SLA/SLO) -->
            @if(isset($slaSummary))
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-bold text-base text-brand-dark flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-brand-primary"></span>
                            Gestión y Análisis de Niveles de Servicio (SLA / SLO / Error Budget)
                        </h3>
                        <p class="text-xs text-brand-muted mt-0.5">Supervisión proactiva de acuerdos de nivel de servicio y cálculo de presupuesto de error.</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded bg-slate-100 text-slate-700">Ventana: 30 Días</span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 text-center">
                        <span class="text-[11px] font-bold uppercase text-brand-muted tracking-wider">Cumplimiento Global SLO</span>
                        <p class="text-2xl font-black text-brand-primary mt-1">{{ $slaSummary['overall_rate'] }}%</p>
                        <span class="text-[10px] text-slate-400">Meta general de servicios</span>
                    </div>
                    <div class="bg-emerald-50/60 p-4 rounded-lg border border-emerald-100 text-center">
                        <span class="text-[11px] font-bold uppercase text-emerald-700 tracking-wider">Cumple</span>
                        <p class="text-2xl font-black text-emerald-600 mt-1">{{ $slaSummary['compliant'] }}</p>
                        <span class="text-[10px] text-emerald-600/70">SLI &ge; SLO objetivo</span>
                    </div>
                    <div class="bg-amber-50/60 p-4 rounded-lg border border-amber-100 text-center">
                        <span class="text-[11px] font-bold uppercase text-amber-700 tracking-wider">En Riesgo (Preventivo)</span>
                        <p class="text-2xl font-black text-amber-600 mt-1">{{ $slaSummary['at_risk'] }}</p>
                        <span class="text-[10px] text-amber-600/70">Error budget consumiéndose</span>
                    </div>
                    <div class="bg-rose-50/60 p-4 rounded-lg border border-rose-100 text-center">
                        <span class="text-[11px] font-bold uppercase text-rose-700 tracking-wider">Incumple</span>
                        <p class="text-2xl font-black text-rose-600 mt-1">{{ $slaSummary['breached'] }}</p>
                        <span class="text-[10px] text-rose-600/70">SLI violó el SLO</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- 2. Tarjetas de Servicios con Histórico para Chart.js y Panel de SLA -->
            <div class="space-y-6">
                @forelse ($services as $service)
                    <div 
                        class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5"
                        x-data="{
                            serviceId: {{ $service->id }},
                            uptime: {{ $service->uptime_24h }},
                            status: '{{ $service->status }}',
                            hasIncident: {{ $service->openIncident ? 'true' : 'false' }},
                            labels: {{ json_encode($service->chart_labels) }},
                            data: {{ json_encode($service->chart_data) }}
                        }"
                        id="service-card-{{ $service->id }}"
                    >
                        <!-- Cabecera de Tarjeta del Servicio -->
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('services.show', $service) }}" class="font-bold text-lg text-brand-dark hover:text-brand-primary transition">
                                        {{ $service->name }}
                                    </a>

                                    @if($service->openIncident)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $service->openIncident->incident_type === 'Down' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $service->openIncident->incident_type }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-mint/15 text-brand-mint">
                                            Online
                                        </span>
                                    @endif

                                    <!-- Badge Clasificación SLA (Cumple / En riesgo / Incumple) -->
                                    @if(isset($service->sla))
                                        @if($service->sla['status'] === 'Cumple')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                ✓ SLA: Cumple
                                            </span>
                                        @elseif($service->sla['status'] === 'En riesgo')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                                                ⚠️ SLA: En Riesgo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                🚨 SLA: Incumple
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                <p class="text-xs font-mono text-brand-muted mt-1">{{ $service->url }}</p>
                            </div>

                            <div class="flex items-center gap-4">
                                <a href="{{ route('services.show', $service) }}" class="text-xs font-semibold text-brand-primary hover:underline">
                                    Ver Detalle SLA & Auditoría &rarr;
                                </a>
                                <div class="text-right border-l border-slate-100 pl-4">
                                    <span class="text-xs font-semibold text-brand-muted uppercase">Uptime 24h</span>
                                    <p class="text-xl font-extrabold text-brand-dark">{{ $service->uptime_24h }}%</p>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de Análisis SLA y Presupuesto de Error del Servicio -->
                        @if(isset($service->sla))
                        <div class="bg-slate-50/75 rounded-lg p-4 border border-slate-100 grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                            <div>
                                <span class="text-brand-muted font-semibold uppercase tracking-wider block text-[10px]">SLI Disponibilidad (30d)</span>
                                <div class="flex items-baseline gap-1 mt-1">
                                    <span class="text-base font-extrabold text-slate-800">{{ $service->sla['uptime_sli'] }}%</span>
                                    <span class="text-slate-400 text-[11px]">/ Meta: {{ $service->sla['slo_availability_target'] }}%</span>
                                </div>
                            </div>

                            <div>
                                <span class="text-brand-muted font-semibold uppercase tracking-wider block text-[10px]">Presupuesto de Error (Error Budget)</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all duration-500 {{ $service->sla['error_budget_percentage'] <= 0 ? 'bg-rose-500' : ($service->sla['error_budget_percentage'] <= 25 ? 'bg-amber-500' : 'bg-emerald-500') }}" 
                                            style="width: {{ min(100, max(0, $service->sla['error_budget_percentage'])) }}%;">
                                        </div>
                                    </div>
                                    <span class="font-bold text-slate-700">{{ $service->sla['error_budget_percentage'] }}%</span>
                                </div>
                                <span class="text-[10px] text-slate-400">Reserva de fallos antes de penalización</span>
                            </div>

                            <div>
                                <span class="text-brand-muted font-semibold uppercase tracking-wider block text-[10px]">SLO Rendimiento / Latencia</span>
                                <div class="flex items-baseline gap-1 mt-1">
                                    <span class="text-base font-bold text-slate-800">{{ $service->sla['avg_latency_ms'] }} ms</span>
                                    <span class="text-slate-400 text-[11px]">(Objetivo: &le; {{ $service->sla['slo_latency_target_ms'] }} ms)</span>
                                </div>
                                <span class="text-[10px] text-slate-400">P95: {{ $service->sla['p95_latency_ms'] }} ms</span>
                            </div>

                            <div>
                                <span class="text-brand-muted font-semibold uppercase tracking-wider block text-[10px]">Histórico SLA (Últimos 7 días)</span>
                                <div class="flex items-center gap-1.5 mt-1.5">
                                    @foreach($service->sla_trend as $snapshot)
                                        <div 
                                            class="w-4 h-4 rounded-sm flex items-center justify-center text-[9px] font-bold text-white {{ $snapshot['status'] === 'Cumple' ? 'bg-emerald-500' : ($snapshot['status'] === 'En riesgo' ? 'bg-amber-500' : 'bg-rose-500') }}"
                                            title="{{ $snapshot['date'] }}: {{ $snapshot['status'] }} (SLI: {{ $snapshot['uptime_sli'] }}%)">
                                        </div>
                                    @endforeach
                                </div>
                                <span class="text-[10px] text-slate-400 mt-1 block">Evolución diaria continua</span>
                            </div>
                        </div>
                        @endif

                        <!-- Gráfica de Latencia (Chart.js / Contenedor) -->
                        <div>
                            <div class="flex items-center justify-between text-xs text-brand-muted mb-2">
                                <span class="font-medium">Histórico de Latencia (Últimos 50 registros)</span>
                                <span>Intervalo: {{ $service->formatted_interval }}</span>
                            </div>

                            <!-- Canvas de Chart.js preparado para Alpine.js -->
                            <div class="h-40 w-full bg-slate-50/50 rounded-lg p-2 border border-slate-100 flex items-center justify-center relative">
                                <canvas 
                                    id="chart-service-{{ $service->id }}" 
                                    class="w-full h-full"
                                    data-labels="{{ json_encode($service->chart_labels) }}"
                                    data-values="{{ json_encode($service->chart_data) }}"
                                ></canvas>
                                @if(empty($service->chart_data))
                                    <span class="absolute text-xs text-slate-400">Sin datos de latencia registrados todavía.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-12 text-center rounded-xl shadow-sm border border-slate-200">
                        <p class="text-brand-muted text-base">
                            Aún no tienes ningún servicio registrado para monitorear.
                        </p>
                        <a href="{{ route('services.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-brand-primary text-white font-semibold rounded-lg shadow-sm text-sm hover:opacity-90 transition">
                            + Crear Mi Primer Endpoint
                        </a>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
