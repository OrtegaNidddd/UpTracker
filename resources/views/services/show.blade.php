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
                <!-- Recarga automática silenciosa cada 1 minuto (sin contador visual) -->
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

            <!-- 1. Tarjetas de Cabecera: Métricas Clave y Semáforos -->
            @php
                $latestLog = $service->latencyLogs->first();
                $latestLatency = $latestLog?->latency_ms;
                $latestLogId = $latestLog?->id ?? 0;
                $threshold = $service->latency_threshold_ms ?? 1000;
                
                // Clasificación de latencia (Semáforo)
                if ($latestLatency === null) {
                    $latColor = 'slate';
                    $latLabel = 'Sin datos';
                    $latClass = 'text-slate-600 bg-slate-100';
                } elseif ($latestLatency < 300) {
                    $latColor = 'emerald';
                    $latLabel = 'Óptima (< 300ms)';
                    $latClass = 'text-emerald-700 bg-emerald-50 border-emerald-200';
                } elseif ($latestLatency <= $threshold) {
                    $latColor = 'amber';
                    $latLabel = 'Aceptable';
                    $latClass = 'text-amber-700 bg-amber-50 border-amber-200';
                } else {
                    $latColor = 'rose';
                    $latLabel = 'Crítica (> umbral)';
                    $latClass = 'text-rose-700 bg-rose-50 border-rose-200';
                }

                // Cálculo SSL
                $hasSsl = str_starts_with(strtolower($service->url), 'https://');
                $sslStatus = $service->ssl_status ?? ($hasSsl ? 'Valid' : 'None');
                $sslDaysRemaining = $service->ssl_expires_at ? (int) now()->diffInDays($service->ssl_expires_at, false) : null;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Tarjeta 1: Uptime 24h -->
                <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Disponibilidad (24h)</span>
                        <span class="w-8 h-8 rounded-xl bg-blue-50 text-brand-primary flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-brand-dark">{{ $service->uptime_24h }}%</span>
                            <span class="text-xs font-semibold {{ $service->uptime_24h >= 99 ? 'text-emerald-600' : ($service->uptime_24h >= 95 ? 'text-amber-600' : 'text-rose-600') }}">
                                {{ $service->uptime_24h >= 99 ? 'Excelente' : ($service->uptime_24h >= 95 ? 'Atención' : 'Crítico') }}
                            </span>
                        </div>
                        <!-- Barra de progreso -->
                        <div class="w-full bg-slate-100 rounded-full h-2 mt-3 overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-500 {{ $service->uptime_24h >= 99 ? 'bg-emerald-500' : ($service->uptime_24h >= 95 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ min(100, max(0, $service->uptime_24h)) }}%"></div>
                        </div>
                    </div>
                    <div class="mt-3 text-[11px] text-slate-400">
                        Calculado sobre verificaciones de las últimas 24 horas.
                    </div>
                </div>

                <!-- Tarjeta 2: Latencia Actual con Semáforo -->
                <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Latencia Actual</span>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full border {{ $latClass }}">
                            {{ $latLabel }}
                        </span>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-brand-dark">
                                {{ $latestLatency !== null ? "{$latestLatency} ms" : '---' }}
                            </span>
                            @if($latestLatency !== null)
                                <span class="text-xs text-slate-400">/ umbral {{ $threshold }} ms</span>
                            @endif
                        </div>
                        <!-- Semáforo visual de 3 luces -->
                        <div class="flex items-center gap-2 mt-3">
                            <div class="flex-1 h-2 rounded-full {{ $latColor === 'emerald' ? 'bg-emerald-500 shadow-sm shadow-emerald-400' : 'bg-slate-200' }}"></div>
                            <div class="flex-1 h-2 rounded-full {{ $latColor === 'amber' ? 'bg-amber-500 shadow-sm shadow-amber-400' : 'bg-slate-200' }}"></div>
                            <div class="flex-1 h-2 rounded-full {{ $latColor === 'rose' ? 'bg-rose-500 shadow-sm shadow-rose-400' : 'bg-slate-200' }}"></div>
                        </div>
                    </div>
                    <div class="mt-3 text-[11px] text-slate-400">
                        Último chequeo: {{ $service->last_checked_at ? $service->last_checked_at->diffForHumans() : 'Pendiente' }}
                    </div>
                </div>

                <!-- Tarjeta 3: Certificado SSL / TLS -->
                <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Certificado SSL / HTTPS</span>
                        @if(!$hasSsl)
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600">No aplica</span>
                        @elseif($sslStatus === 'Valid')
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Válido
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                {{ $sslStatus }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-4">
                        @if($hasSsl)
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-black text-brand-dark">
                                    {{ $sslDaysRemaining !== null ? ($sslDaysRemaining > 0 ? "{$sslDaysRemaining} días" : 'Expirado') : 'Protegido' }}
                                </span>
                                @if($sslDaysRemaining !== null && $sslDaysRemaining > 0)
                                    <span class="text-xs text-slate-400">restantes</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Expira: {{ $service->ssl_expires_at ? $service->ssl_expires_at->format('d/m/Y') : 'Verificación automática activa' }}
                            </p>
                        @else
                            <div class="text-slate-600 font-medium text-sm">
                                Endpoint sin cifrado SSL (HTTP simple)
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Recomendamos migrar a HTTPS para mayor seguridad.</p>
                        @endif
                    </div>
                    <div class="mt-3 text-[11px] text-slate-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Monitoreo continuo de caducidad
                    </div>
                </div>
            </div>

            <!-- 2. Gráfica de Latencia Detallada (Chart.js) -->
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

                        // 1. Manejador de evento en tiempo real
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

                        // 2. Conexión en tiempo real vía WebSockets (Laravel Echo)
                        if (window.Echo) {
                            const channel = window.Echo.private('user.{{ auth()->id() }}.services');
                            channel.listen('EndpointStatusUpdated', handleDetailUpdate);
                        }

                    }
                }"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="font-bold text-lg text-brand-dark">Curva de Latencia de Red</h3>
                        <p class="text-xs text-brand-muted">Historial cronológico de los últimos {{ count($chartData) }} sondeos</p>
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

            <!-- 3. Dos Columnas: Tabla de Verificaciones & Historial de Incidentes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Columna Izquierda: Tabla de Verificaciones de Red -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-base text-brand-dark">Registro de Sondeos de Red</h3>
                            <p class="text-xs text-brand-muted">Últimas {{ $service->latencyLogs->count() }} comprobaciones</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-brand-primary">
                            {{ $service->formatted_interval }}
                        </span>
                    </div>

                    <div class="overflow-x-auto max-h-[460px]">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-500 uppercase font-semibold sticky top-0 border-b border-slate-100 z-10">
                                <tr>
                                    <th class="p-3">Hora / Fecha</th>
                                    <th class="p-3">Estado</th>
                                    <th class="p-3">Código HTTP</th>
                                    <th class="p-3 text-right">Latencia</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($service->latencyLogs as $log)
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="p-3 font-medium text-slate-700">
                                            {{ $log->checked_at ? $log->checked_at->format('d/m H:i:s') : '---' }}
                                            <span class="block text-[10px] text-slate-400 font-normal">
                                                {{ $log->checked_at ? $log->checked_at->diffForHumans() : '' }}
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            @if($log->status === 'Up')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Up
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Down
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-3 font-mono font-semibold">
                                            @php
                                                $http = $log->http_status_code;
                                                $codeBadge = match(true) {
                                                    $http >= 200 && $http < 300 => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                                                    $http >= 300 && $http < 400 => 'text-blue-700 bg-blue-50 border-blue-200',
                                                    $http >= 400 && $http < 500 => 'text-amber-700 bg-amber-50 border-amber-200',
                                                    default => 'text-rose-700 bg-rose-50 border-rose-200',
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 rounded border text-[11px] {{ $codeBadge }}">
                                                {{ $http ?? 'ERR' }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right">
                                            <span class="font-bold text-slate-700">{{ $log->latency_ms !== null ? "{$log->latency_ms} ms" : '---' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-slate-400">
                                            Sin registros de red en el historial reciente.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Columna Derecha: Bitácora de Incidentes -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-base text-brand-dark">Bitácora de Incidentes y Caídas</h3>
                            <p class="text-xs text-brand-muted">Historial de anomalías registradas</p>
                        </div>
                        @if($service->openIncident)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-500 text-white animate-pulse">
                                Incidente Activo
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                                Todo Operativo
                            </span>
                        @endif
                    </div>

                    <div class="p-5 flex-1 space-y-3 overflow-y-auto max-h-[460px]">
                        @forelse ($service->incidents as $incident)
                            <div class="p-4 rounded-xl border {{ $incident->resolved_at === null ? 'bg-rose-50/50 border-rose-200' : 'bg-slate-50/60 border-slate-200' }} transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $incident->incident_type === 'Down' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $incident->incident_type === 'Down' ? 'Caída Total (Down)' : 'Degradación (Lento)' }}
                                        </span>

                                        @if($incident->resolved_at === null)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-600 text-white uppercase tracking-wider animate-pulse">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span> En curso
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-700">
                                                Resuelto
                                            </span>
                                        @endif
                                    </div>

                                    <span class="text-[11px] font-medium text-slate-500">
                                        {{ $incident->started_at ? $incident->started_at->format('d/m/Y H:i') : '' }}
                                    </span>
                                </div>

                                <p class="mt-2 text-xs text-slate-700 leading-relaxed font-medium">
                                    {{ $incident->details ?? 'Se detectó fallo en la respuesta HTTP o tiempo de espera excedido.' }}
                                </p>

                                <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500">
                                    <span>
                                        Duración: 
                                        <strong class="text-slate-700">
                                            @if($incident->resolved_at)
                                                {{ $incident->duration_seconds ? gmdate("H:i:s", $incident->duration_seconds) : $incident->started_at->diffForHumans($incident->resolved_at, true) }}
                                            @else
                                                {{ $incident->started_at ? $incident->started_at->diffForHumans(null, true) : 'Calculando...' }}
                                            @endif
                                        </strong>
                                    </span>
                                    <span>
                                        @if($incident->resolved_at)
                                            Resuelto: {{ $incident->resolved_at->format('H:i') }}
                                        @else
                                            <span class="text-rose-600 font-semibold">Esperando recuperación...</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @empty
                            <!-- Empty State de Incidentes -->
                            <div class="text-center py-12 px-4 space-y-3">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="font-bold text-sm text-brand-dark">¡Sin incidentes reportados!</h4>
                                <p class="text-xs text-brand-muted max-w-xs mx-auto">
                                    Este servicio ha mantenido una estabilidad ejemplar sin registrar caídas ni degradaciones recientes.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Modal para Editar Servicio -->
    <x-modal name="edit-service-modal" focusable>
        <form method="POST" action="{{ route('services.update', $service) }}" class="p-6">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-bold text-brand-dark">
                Editar Configuración de {{ $service->name }}
            </h2>
            <p class="mt-1 text-sm text-brand-muted">
                Ajusta las opciones de supervisión y umbrales de latencia para este endpoint.
            </p>

            <div class="mt-6 space-y-4 text-xs">
                <div>
                    <x-input-label for="edit_name" value="Nombre descriptivo" class="text-slate-700 font-semibold" />
                    <x-text-input id="edit_name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $service->name) }}" required />
                </div>

                <div>
                    <x-input-label for="edit_url" value="URL del Endpoint" class="text-slate-700 font-semibold" />
                    <x-text-input id="edit_url" name="url" type="url" class="mt-1 block w-full font-mono text-xs" value="{{ old('url', $service->url) }}" required />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="edit_http_method" value="Método HTTP" class="text-slate-700 font-semibold" />
                        <select id="edit_http_method" name="http_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-xs text-xs">
                            <option value="GET" @selected(old('http_method', $service->http_method) === 'GET')>GET (Descarga respuesta)</option>
                            <option value="HEAD" @selected(old('http_method', $service->http_method) === 'HEAD')>HEAD (Solo cabeceras, ultra rápido)</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="edit_check_interval" value="Intervalo de Chequeo" class="text-slate-700 font-semibold" />
                        <select id="edit_check_interval" name="check_interval" class="mt-1 block w-full border-gray-300 rounded-md shadow-xs text-xs">
                            <option value="30" @selected(old('check_interval', $service->check_interval) == 30)>Cada 30 segundos</option>
                            <option value="60" @selected(old('check_interval', $service->check_interval) == 60)>Cada 1 minuto</option>
                            <option value="300" @selected(old('check_interval', $service->check_interval) == 300)>Cada 5 minutos</option>
                            <option value="600" @selected(old('check_interval', $service->check_interval) == 600)>Cada 10 minutos</option>
                            <option value="900" @selected(old('check_interval', $service->check_interval) == 900)>Cada 15 minutos</option>
                            <option value="1800" @selected(old('check_interval', $service->check_interval) == 1800)>Cada 30 minutos</option>
                            <option value="3600" @selected(old('check_interval', $service->check_interval) == 3600)>Cada 1 hora</option>
                        </select>
                    </div>
                </div>

                <div>
                    <x-input-label for="edit_latency_threshold_ms" value="Umbral de Alerta de Latencia (ms)" class="text-slate-700 font-semibold" />
                    <x-text-input id="edit_latency_threshold_ms" name="latency_threshold_ms" type="number" min="50" max="60000" class="mt-1 block w-full" value="{{ old('latency_threshold_ms', $service->latency_threshold_ms ?? 1000) }}" placeholder="1000" />
                    <p class="text-[11px] text-slate-400 mt-1">Si la latencia supera este valor se creará una degradación.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-primary-button class="bg-brand-primary hover:bg-blue-600">
                    Guardar Cambios
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
