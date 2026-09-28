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

            <!-- 2. Tarjetas de Servicios con Histórico para Chart.js -->
            <div class="space-y-6">
                @forelse ($services as $service)
                    <div 
                        class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4"
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
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <h3 class="font-bold text-lg text-brand-dark">{{ $service->name }}</h3>
                                    @if($service->openIncident)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $service->openIncident->incident_type === 'Down' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $service->openIncident->incident_type }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-mint/15 text-brand-mint">
                                            Online
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs font-mono text-brand-muted mt-1">{{ $service->url }}</p>
                            </div>

                            <div class="text-right">
                                <span class="text-xs font-semibold text-brand-muted uppercase">Uptime 24h</span>
                                <p class="text-xl font-extrabold text-brand-dark">{{ $service->uptime_24h }}%</p>
                            </div>
                        </div>

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
