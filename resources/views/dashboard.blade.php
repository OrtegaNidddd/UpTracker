<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="font-extrabold text-2xl text-brand-dark leading-tight flex items-center gap-2">
                        <svg class="w-6 h-6 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Dashboard de Telemetría en Tiempo Real
                    </h2>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        WebSockets Activo
                    </span>
                </div>
                <p class="text-xs text-brand-muted mt-1">Supervisión reactiva vía Laravel Reverb con curvas de latencia Chart.js actualizadas en vivo.</p>
            </div>

            <div class="flex items-center gap-2">
                <!-- Recarga automática silenciosa cada 1 minuto (sin contador visual) -->
                <script>
                    setTimeout(() => window.location.reload(), 60000);
                </script>

                <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold rounded-lg shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Gestionar Endpoints
                </a>

                <a href="{{ route('notification-channels.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold rounded-lg shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Canales de Alerta
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 1. Tarjetas de Resumen General -->
            @php
                $totalServices = $services->count();
                $avgUptime = $totalServices > 0 ? round($services->avg('uptime_24h'), 2) : 100.0;
                $activeIncidentsCount = $services->filter(fn ($s) => $s->openIncident !== null)->count();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Servicios Supervisados</span>
                    <p class="text-3xl font-black text-brand-dark mt-2">{{ $totalServices }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Disponibilidad Promedio (24h)</span>
                    <p class="text-3xl font-black text-emerald-600 mt-2">{{ $avgUptime }}%</p>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Incidentes Activos</span>
                    <p class="text-3xl font-black {{ $activeIncidentsCount > 0 ? 'text-rose-600' : 'text-slate-700' }} mt-2">
                        {{ $activeIncidentsCount }}
                    </p>
                </div>
            </div>

            <!-- 2. Tarjetas de Servicios con Histórico y Chart.js en Tiempo Real -->
            <div class="space-y-6">
                @forelse ($services as $service)
                    @php
                        $latestLog = $service->latency_history?->last() ?? $service->latencyLogs->first();
                        $currentLatency = $latestLog?->latency_ms;
                        $latestLogId = $latestLog?->id ?? 0;
                    @endphp
                    <div 
                        class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 space-y-4 transition hover:shadow-md"
                        x-data="{
                            serviceId: {{ $service->id }},
                            uptime: {{ $service->uptime_24h }},
                            status: '{{ $service->status }}',
                            currentLatency: {{ $currentLatency !== null ? $currentLatency : 'null' }},
                            labels: {{ json_encode($service->chart_labels) }},
                            data: {{ json_encode($service->chart_data) }},
                            lastLogId: {{ $latestLogId }},
                            chart: null,
                            init() {
                                const canvas = document.getElementById('chart-service-' + this.serviceId);
                                if (canvas && window.Chart) {
                                    const ctx = canvas.getContext('2d');
                                    const gradient = ctx.createLinearGradient(0, 0, 0, 160);
                                    gradient.addColorStop(0, 'rgba(0, 112, 243, 0.22)');
                                    gradient.addColorStop(1, 'rgba(0, 112, 243, 0.00)');

                                    this.chart = new window.Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: this.labels,
                                            datasets: [{
                                                data: this.data,
                                                borderColor: '#0070F3',
                                                borderWidth: 2,
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
                                            animation: { duration: 400 },
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
                                                        label: (c) => `Latencia: ${c.parsed.y} ms`,
                                                    }
                                                }
                                            },
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    grid: { color: 'rgba(241, 245, 249, 1)' },
                                                    ticks: { color: '#94A3B8', font: { size: 10 }, callback: v => `${v}ms` }
                                                },
                                                x: {
                                                    grid: { display: false },
                                                    ticks: { color: '#94A3B8', font: { size: 10 }, maxTicksLimit: 8 }
                                                }
                                            }
                                        }
                                    });
                                }

                                // 1. Manejador unificado de actualización de telemetría
                                const handleUpdate = (e) => {
                                    if (Number(e.service_id) === Number(this.serviceId)) {
                                        this.currentLatency = e.latency_ms;
                                        this.status = e.status === 'Up' ? 'online' : 'offline';
                                        
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

                                        if (e.status !== 'Up') {
                                            window.dispatchEvent(new CustomEvent('notify', {
                                                detail: { message: `Alerta: Endpoint {{ $service->name }} ha caído (${e.http_status_code || 'Error de conexión'})`, type: 'error' }
                                            }));
                                        }
                                    }
                                };

                                // 2. Conexión a WebSocket Reverb vía Laravel Echo
                                if (window.Echo) {
                                    const channel = window.Echo.private('user.{{ auth()->id() }}.services');
                                    channel.listen('EndpointStatusUpdated', handleUpdate);
                                }

                            }
                        }"
                        id="service-card-{{ $service->id }}"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <h3 class="font-bold text-lg text-brand-dark">
                                        <a href="{{ route('services.show', $service) }}" class="hover:text-brand-primary transition">
                                            {{ $service->name }}
                                        </a>
                                    </h3>
                                    
                                    <!-- Badge reactivo según Alpine status -->
                                    <template x-if="status === 'online'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Online
                                        </span>
                                    </template>
                                    <template x-if="status === 'degraded'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Lento
                                        </span>
                                    </template>
                                    <template x-if="status === 'offline'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                            Offline
                                        </span>
                                    </template>

                                    <!-- Latencia actual reactiva -->
                                    <template x-if="currentLatency !== null">
                                        <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                                            <span x-text="currentLatency + ' ms'"></span>
                                        </span>
                                    </template>
                                </div>
                                <p class="text-xs font-mono text-brand-muted mt-1">{{ $service->url }}</p>
                            </div>

                            <div class="flex items-center gap-5">
                                <div class="text-right">
                                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Uptime 24h</span>
                                    <p class="text-xl font-black text-brand-dark">{{ $service->uptime_24h }}%</p>
                                </div>

                                <a href="{{ route('services.show', $service) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition" title="Ver métricas avanzadas">
                                    <span>Inspeccionar</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Gráfica de Latencia (Chart.js / Contenedor) -->
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-500 mb-2 font-medium">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-0.5 bg-brand-primary inline-block"></span>
                                    Telemetría de Latencia en Vivo (Últimos 50 registros)
                                </span>
                                <span>Intervalo: {{ $service->formatted_interval }}</span>
                            </div>

                            <div class="h-40 w-full bg-slate-50/50 rounded-xl p-2 border border-slate-100 flex items-center justify-center relative">
                                <canvas 
                                    id="chart-service-{{ $service->id }}" 
                                    class="w-full h-full"
                                ></canvas>
                                <template x-if="data.length === 0">
                                    <span class="absolute text-xs text-slate-400 font-medium">Esperando primeras mediciones de red...</span>
                                </template>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-12 text-center rounded-2xl shadow-xs border border-slate-200/80">
                        <div class="max-w-sm mx-auto space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-brand-primary mx-auto flex items-center justify-center">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h4 class="font-bold text-base text-brand-dark">Aún no tienes ningún servicio registrado</h4>
                            <p class="text-xs text-brand-muted">Registra tu primera API o sitio para comenzar a recibir telemetría de latencia en tiempo real.</p>
                            <a href="{{ route('services.index') }}" class="mt-2 inline-flex items-center px-4 py-2 bg-brand-primary text-white font-semibold rounded-xl shadow-md text-xs hover:bg-blue-600 transition">
                                + Crear Mi Primer Endpoint
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
