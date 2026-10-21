<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('services.index') }}" class="p-2 text-slate-400 hover:text-brand-dark hover:bg-slate-100 rounded-xl transition" title="Volver al listado">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="font-extrabold text-2xl text-brand-dark tracking-tight">
                            {{ $service->name }}
                        </h2>
                        @if($service->status === 'online')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                            </span>
                        @elseif($service->status === 'degraded')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-600 border border-amber-500/20">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Lento / Degradado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-600 border border-rose-500/20">
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span> Offline (Caído)
                            </span>
                        @endif

                        <!-- Badge SLA Integrado -->
                        @if(isset($sla['status']))
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold uppercase {{ $sla['status'] === 'Cumple' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($sla['status'] === 'En riesgo' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-rose-100 text-rose-800 border border-rose-300') }}">
                                SLA: {{ $sla['status'] }}
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-brand-muted">
                        <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-700 font-semibold">{{ $service->http_method ?? 'GET' }}</span>
                        <a href="{{ $service->url }}" target="_blank" rel="noopener noreferrer" class="hover:text-brand-primary underline truncate max-w-md inline-flex items-center gap-1 font-mono">
                            {{ $service->url }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        <span>&bull;</span>
                        <span>Chequeo: <strong>{{ $service->formatted_interval }}</strong></span>
                        <span>&bull;</span>
                        <span>Umbral alerta: <strong>{{ $service->latency_threshold_ms ?? 1000 }} ms</strong></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Recarga automática silenciosa cada 1 minuto -->
                <script>
                    setTimeout(() => window.location.reload(), 60000);
                </script>

                <a href="{{ route('dashboard') }}#service-card-{{ $service->id }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold rounded-lg shadow-xs transition">
                    <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/></svg>
                    Ver en Dashboard
                </a>

                <button 
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'edit-service-modal')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Editar Configuración
                </button>

                <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('¿Confirmas que deseas eliminar este servicio y todo su historial de monitoreo?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 border border-rose-200 rounded-lg transition" title="Eliminar servicio">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Mensajes Flash -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-semibold rounded-xl">
                    {{ session('success') }}
                </div>
            @endif

            <!-- 1. Tarjetas Resumen de Nivel de Servicio (SLA / SLO / Error Budget) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">SLI Disponibilidad (30d)</span>
                    <p class="text-3xl font-black text-brand-dark mt-1">{{ $sla['uptime_sli'] }}%</p>
                    <span class="text-[11px] text-slate-400">Meta pactada (SLO): {{ $sla['slo_availability_target'] }}%</span>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Presupuesto de Error</span>
                    <p class="text-3xl font-black {{ $sla['error_budget_percentage'] <= 0 ? 'text-rose-600' : ($sla['error_budget_percentage'] <= 25 ? 'text-amber-600' : 'text-emerald-600') }} mt-1">
                        {{ $sla['error_budget_percentage'] }}%
                    </p>
                    <span class="text-[11px] text-slate-400">Margen de fallo antes de penalización</span>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Latencia Promedio / P95</span>
                    <p class="text-3xl font-black text-slate-800 mt-1">{{ $sla['avg_latency_ms'] }} <span class="text-sm font-normal text-slate-400">ms</span></p>
                    <span class="text-[11px] text-slate-400">P95: {{ $sla['p95_latency_ms'] }} ms | Meta: &le; {{$sla['slo_latency_target_ms'] }} ms</span>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Cumplimiento de Latencia</span>
                    <p class="text-3xl font-black text-brand-primary mt-1">{{ $sla['latency_compliance_rate'] }}%</p>
                    <span class="text-[11px] text-slate-400">{{ $sla['fast_checks'] }} de {{$sla['total_checks'] }} mediciones</span>
                </div>
            </div>

            <!-- 2. Diagnóstico del Nivel de Servicio & Estado SSL -->
            @php
                $latestLog =$service->latencyLogs->first();
                $latestLatency =$latestLog?->latency_ms;
                $latestLogId =$latestLog?->id ?? 0;
                $threshold =$service->latency_threshold_ms ?? 1000;
                
                $hasSsl = str_starts_with(strtolower($service->url), 'https://');$sslStatus = $service->ssl_status ?? ($hasSsl ? 'Valid' : 'None');
                $sslDaysRemaining = $service->ssl_expires_at ? (int) now()->diffInDays($service->ssl_expires_at, false) : null;
            @endphp

            <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-base text-slate-800">Diagnóstico del Nivel de Servicio & Seguridad</h3>
                    <span class="font-mono text-xs text-slate-500">{{ $service->url }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl {{ $sla['status'] === 'Cumple' ? 'bg-emerald-50 border border-emerald-200 text-emerald-900' : ($sla['status'] === 'En riesgo' ? 'bg-amber-50 border border-amber-200 text-amber-900' : 'bg-rose-50 border border-rose-200 text-rose-900') }}">
                        <div class="flex items-center gap-2 font-bold text-sm">
                            <span>{{ $sla['status'] === 'Cumple' ? '✅' : ($sla['status'] === 'En riesgo' ? '⚠️' : '🚨') }}</span>
                            <span>Estado SLA: {{ $sla['status'] }}</span>
                        </div>
                        <p class="text-xs mt-1.5 opacity-90">{{ $sla['message'] }}</p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Certificado SSL / HTTPS</span>
                            <p class="text-sm font-bold text-slate-800 mt-1">
                                @if($hasSsl)
                                    {{ $sslDaysRemaining !== null ? "Válido por {$sslDaysRemaining} días" : 'Protegido con TLS' }}
                                @else
                                    Sin cifrado HTTPS (Conexión insegura)
                                @endif
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $hasSsl ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                            {{ $hasSsl ? 'HTTPS Activo' : 'HTTP Simple' }}
                        </span>
                    </div>
                </div>

                <!-- Barra de Presupuesto de Error -->
                <div class="space-y-1.5 pt-2">
                    <div class="flex justify-between text-xs font-medium text-slate-600">
                        <span>Consumo de Presupuesto de Error (Error Budget)</span>
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

            <!-- 3. Gráfica de Latencia Detallada (Chart.js con WebSockets) -->
            <div 
                class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 space-y-4"
                x-data="{
                    labels: {{ json_encode($chartLabels) }},
                    data: {{ json_encode($chartData) }},
                    lastLogId: {{ $latestLogId }},
                    chart: null,
                    init() {
                        const ctx = document.getElementById('service-detail-chart');
                        if (!ctx) return;
                        
                        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 240);
                        gradient.addColorStop(0, 'rgba(0, 112, 243, 0.25)');
                        gradient.addColorStop(1, 'rgba(0, 112, 243, 0.00)');

                        this.chart = new window.Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: this.labels,
                                datasets: [{
                                    label: 'Latencia (ms)',
                                    data: this.data,
                                    borderColor: '#0070F3',
                                    borderWidth: 2.5,
                                    backgroundColor: gradient,
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 2.5,
                                    pointHoverRadius: 6,
                                    pointHitRadius: 12,
                                    pointBackgroundColor: '#0070F3',
                                    pointBorderColor: '#FFFFFF',
                                    pointBorderWidth: 1.5,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        enabled: true,
                                        backgroundColor: '#0D192B',
                                        titleFont: { size: 12, weight: 'bold' },
                                        bodyFont: { size: 12 },
                                        padding: 10,
                                        cornerRadius: 8,
                                        callbacks: {
                                            title: (items) => items.length ? `Hora: ${items[0].label}` : '',
                                            label: (ctx) => `Latencia: ${ctx.parsed.y} ms`
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                                        ticks: {
                                            color: '#5A6A80',
                                            callback: (val) => `${val}ms`
                                        }
                                    },
                                    x: {
                                        grid: { display: false },
                                        ticks: { color: '#5A6A80', maxTicksLimit: 10 }
                                    }
                                }
                            }
                        });

                        const handleDetailUpdate = (e) => {
                            if (Number(e.service_id) === Number({{ $service->id }})) {
                                const time = new Date(e.checked_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                                this.labels.push(time);
                                this.data.push(e.latency_ms || 0);
                                if (this.labels.length > 50) {
                                    this.labels.shift();
                                    this.data.shift();
                                }
                                if (this.chart) {
                                    this.chart.data.labels = [...this.labels];
                                    this.chart.data.datasets[0].data = [...this.data];
                                    this.chart.update('none');
                                }
                            }
                        };

                        if (window.Echo) {
                            const channel = window.Echo.private('user.{{ auth()->id() }}.services');
                            channel.listen('EndpointStatusUpdated', handleDetailUpdate);
                        }
                    }
                }"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="font-bold text-lg text-brand-dark">Curva de Latencia de Red en Vivo</h3>
                        <p class="text-xs text-brand-muted">Historial cronológico de sondeos conectados vía WebSockets</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5 text-slate-600">
                            <span class="w-3 h-0.5 bg-brand-primary inline-block"></span> Latencia (ms)
                        </span>
                        <span class="text-slate-400">|</span>
                        <span class="text-slate-500">Umbral: <strong class="text-slate-700">{{ $threshold }} ms</strong></span>
                    </div>
                </div>

                <div class="h-64 w-full relative">
                    <canvas id="service-detail-chart"></canvas>
                    @if(empty($chartData))
                        <div class="absolute inset-0 flex items-center justify-center bg-slate-50/80 rounded-xl">
                            <span class="text-xs text-slate-400 font-medium">Aún no se han recolectado mediciones de latencia para este endpoint.</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 4. Tabla Histórica Diaria de SLA & Bitácora de Incidentes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Bitácora Histórica de SLA (Snapshots) -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-base text-brand-dark">Bitácora Histórica de SLA</h3>
                            <p class="text-xs text-brand-muted">Registro de Sondeos de Red y Snapshots de cumplimiento</p>
                        </div>
                        <span class="text-xs text-slate-400">{{ $service->slaHistories->count() }} registros</span>
                    </div>

                    <div class="overflow-x-auto max-h-[420px]">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-50 text-slate-500 uppercase font-semibold sticky top-0 border-b border-slate-100">
                                <tr>
                                    <th class="p-3.5">Fecha</th>
                                    <th class="p-3.5">SLI Uptime</th>
                                    <th class="p-3.5">Latencia Prom.</th>
                                    <th class="p-3.5">Error Budget</th>
                                    <th class="p-3.5">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($service->slaHistories as $history)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-3.5 font-bold text-slate-800">{{ $history->snapshot_date->format('Y-m-d') }}</td>
                                        <td class="p-3.5 font-bold">{{ $history->uptime_sli }}%</td>
                                        <td class="p-3.5">{{ $history->avg_latency_ms }} ms</td>
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
                                        <td colspan="5" class="p-8 text-center text-slate-400">
                                            Aún no hay fotografías históricas registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bitácora de Incidentes -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-base text-brand-dark">Bitácora de Incidentes y Caídas</h3>
                            <p class="text-xs text-brand-muted">Historial de anomalías</p>
                        </div>
                        @if($service->openIncident)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500 text-white animate-pulse">
                                Incidente Activo
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                                Todo Operativo
                            </span>
                        @endif
                    </div>

                    <div class="p-5 flex-1 space-y-3 overflow-y-auto max-h-[420px]">
                        @forelse ($service->incidents as $incident)
                            <div class="p-4 rounded-xl border {{ $incident->resolved_at === null ? 'bg-rose-50/50 border-rose-200' : 'bg-slate-50/60 border-slate-200' }} transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $incident->incident_type === 'Down' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $incident->incident_type === 'Down' ? 'Caída Total (Down)' : 'Degradación (Lento)' }}
                                        </span>
                                        @if($incident->resolved_at === null)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-600 text-white uppercase tracking-wider animate-pulse">
                                                En curso
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-700">
                                                Resuelto
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-[11px] text-slate-500">{{ $incident->started_at ? $incident->started_at->format('d/m/Y H:i') : '' }}</span>
                                </div>
                                <p class="mt-2 text-xs text-slate-700 leading-relaxed font-medium">
                                    {{ $incident->details ?? 'Se detectó fallo en la respuesta HTTP o tiempo de espera excedido.' }}
                                </p>
                            </div>
                        @empty
                            <div class="text-center py-12 px-4 space-y-2">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="font-bold text-sm text-brand-dark">¡Sin incidentes reportados!</h4>
                                <p class="text-xs text-brand-muted max-w-xs mx-auto">
                                    Este servicio ha mantenido una estabilidad ejemplar sin registrar caídas recientes.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Modal para Editar Configuración y SLOs -->
    <x-modal name="edit-service-modal" focusable>
        <form method="POST" action="{{ route('services.update', $service) }}" class="p-6 text-xs space-y-4">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-bold text-brand-dark">
                Editar Configuración y Objetivos SLA/SLO
            </h2>
            <p class="mt-1 text-xs text-brand-muted">
                Modifica los umbrales de disponibilidad, latencia y parámetros del endpoint.
            </p>

            <div class="space-y-4">
                <div>
                    <x-input-label for="edit_name" value="Nombre del Servicio" class="font-semibold text-slate-700" />
                    <x-text-input id="edit_name" name="name" type="text" class="mt-1 block w-full text-xs" :value="old('name', $service->name)" required />
                </div>

                <div>
                    <x-input-label for="edit_url" value="URL del Endpoint" class="font-semibold text-slate-700" />
                    <x-text-input id="edit_url" name="url" type="url" class="mt-1 block w-full font-mono text-xs" :value="old('url', $service->url)" required />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="edit_http_method" value="Método HTTP" class="font-semibold text-slate-700" />
                        <select id="edit_http_method" name="http_method" class="mt-1 block w-full border-slate-300 rounded-lg text-xs focus:border-brand-primary focus:ring-brand-primary">
                            <option value="GET" @selected(strtoupper($service->http_method) === 'GET')>GET</option>
                            <option value="HEAD" @selected(strtoupper($service->http_method) === 'HEAD')>HEAD</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="edit_check_interval" value="Intervalo de Chequeo" class="font-semibold text-slate-700" />
                        <select id="edit_check_interval" name="check_interval" class="mt-1 block w-full border-slate-300 rounded-lg text-xs focus:border-brand-primary focus:ring-brand-primary">
                            <option value="30" @selected($service->check_interval == 30)>Cada 30 segundos</option>
                            <option value="60" @selected($service->check_interval == 60)>Cada 1 minuto</option>
                            <option value="300" @selected($service->check_interval == 300)>Cada 5 minutos</option>
                            <option value="600" @selected($service->check_interval == 600)>Cada 10 minutos</option>
                            <option value="900" @selected($service->check_interval == 900)>Cada 15 minutos</option>
                            <option value="1800" @selected($service->check_interval == 1800)>Cada 30 minutos</option>
                            <option value="3600" @selected($service->check_interval == 3600)>Cada 1 hora</option>
                        </select>
                    </div>
                </div>

                <!-- Objetivos SLA / SLO -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Objetivos de Nivel de Servicio (SLO)</h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="edit_slo_availability_target" value="SLO Disponibilidad (%)" class="text-slate-600 text-[11px]" />
                            <x-text-input id="edit_slo_availability_target" name="slo_availability_target" type="number" step="0.01" min="50" max="100" class="mt-1 block w-full text-xs" :value="old('slo_availability_target', $service->slo_availability_target ?? '99.00')" required />
                        </div>

                        <div>
                            <x-input-label for="edit_slo_latency_target_ms" value="SLO Latencia Máx (ms)" class="text-slate-600 text-[11px]" />
                            <x-text-input id="edit_slo_latency_target_ms" name="slo_latency_target_ms" type="number" min="10" max="30000" class="mt-1 block w-full text-xs" :value="old('slo_latency_target_ms', $service->slo_latency_target_ms ?? '500')" required />
                        </div>
                    </div>
                </div>

                <div>
                    <x-input-label for="edit_latency_threshold_ms" value="Umbral de Alerta de Latencia (ms)" class="font-semibold text-slate-700" />
                    <x-text-input id="edit_latency_threshold_ms" name="latency_threshold_ms" type="number" min="50" max="60000" class="mt-1 block w-full text-xs" :value="old('latency_threshold_ms', $service->latency_threshold_ms ?? 1000)" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-primary-button class="bg-brand-primary hover:bg-blue-600 text-xs">
                    Guardar Cambios
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>