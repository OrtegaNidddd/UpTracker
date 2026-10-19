<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página de Estado y Nivel de Servicio (SLA) - UpTracker</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-800 min-h-screen flex flex-col justify-between"
    x-data="{
        loading: true,
        data: null,
        lastUpdated: '',
        fetchStatus() {
            this.loading = true;
            fetch('{{ route('status.public') }}')
                .then(r => r.json())
                .then(json => {
                    this.data = json;
                    this.lastUpdated = new Date().toLocaleTimeString();
                    this.loading = false;
                })
                .catch(err => {
                    console.error('Error cargando estado:', err);
                    this.loading = false;
                });
        }
    }"
    x-init="fetchStatus(); setInterval(() => fetchStatus(), 30000);">

    <!-- Header Público -->
    <header class="border-b border-slate-200 bg-white shadow-sm sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand-primary flex items-center justify-center text-white shadow-sm font-bold text-lg">
                        U
                    </div>
                    <div>
                        <span class="font-extrabold text-xl text-slate-900 tracking-tight">UpTracker</span>
                        <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Página de Estado Pública & SLA</span>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-4">
                <button @click="fetchStatus()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    <svg class="w-3.5 h-3.5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Actualizar</span>
                </button>

                @auth
                    <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-brand-primary hover:underline">Ir al Panel</a>
                @else
                    <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">Iniciar Sesión</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex-1 w-full space-y-6">

        <!-- Banner Estado Global -->
        <template x-if="data">
            <div 
                class="p-6 rounded-2xl shadow-sm border transition-all"
                :class="{
                    'bg-emerald-500 text-white border-emerald-600': data.status === 'Operational',
                    'bg-amber-500 text-white border-amber-600': data.status === 'Degraded_Performance' || data.status === 'Partial_Outage',
                    'bg-rose-600 text-white border-rose-700': data.status === 'Major_Outage',
                    'bg-slate-100 text-slate-700 border-slate-300': data.status === 'No_Services'
                }">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shrink-0">
                            <template x-if="data.status === 'Operational'">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="data.status !== 'Operational'">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </template>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black" x-text="data.summary"></h1>
                            <p class="text-xs text-white/80 mt-0.5">Última comprobación: <span x-text="lastUpdated"></span> (Actualización automática cada 30s)</p>
                        </div>
                    </div>
                    <div class="text-right sm:border-l sm:border-white/20 sm:pl-6">
                        <span class="text-xs uppercase font-bold tracking-wider text-white/80">Servicios Operativos</span>
                        <p class="text-2xl font-black" x-text="`${data.online_count} / ${data.total_monitored}`"></p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Tarjetas Resumen de Nivel de Servicio (SLA / SLO) -->
        <template x-if="data && data.sla_summary">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Cumplimiento Global SLO</span>
                    <p class="text-2xl font-extrabold text-brand-primary mt-1" x-text="`${data.sla_summary.overall_compliance_rate}%`"></p>
                    <p class="text-[11px] text-slate-400 mt-1">Meta agregada de servicios</p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Servicios Cumplen</span>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1" x-text="data.sla_summary.compliant_count"></p>
                    <p class="text-[11px] text-slate-400 mt-1">SLI &ge; SLO objetivo</p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-600">Servicios En Riesgo</span>
                    <p class="text-2xl font-extrabold text-amber-600 mt-1" x-text="data.sla_summary.at_risk_count"></p>
                    <p class="text-[11px] text-slate-400 mt-1">Presupuesto de error bajo</p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Servicios Incumplen</span>
                    <p class="text-2xl font-extrabold text-rose-600 mt-1" x-text="data.sla_summary.breached_count"></p>
                    <p class="text-[11px] text-slate-400 mt-1">SLI inferior a la meta</p>
                </div>
            </div>
        </template>

        <!-- Lista de Servicios y Estado de SLA -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Estado de Servicios y Cumplimiento SLA</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Métricas de disponibilidad, latencia y objetivos pactados (SLO).</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded bg-slate-100 text-slate-600">Ventana de Auditoría: 30 Días</span>
            </div>

            <!-- Loader -->
            <div x-show="loading && !data" class="p-12 text-center text-slate-400 text-sm">
                <svg class="w-8 h-8 animate-spin mx-auto text-brand-primary mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Cargando estado del sistema...
            </div>

            <!-- Contenido cuando ya hay datos -->
            <div x-show="data" class="divide-y divide-slate-100">
                <template x-for="service in (data ? data.services : [])" :key="service.id">
                    <div class="p-6 hover:bg-slate-50/50 transition flex flex-col md:flex-row md:items-center justify-between gap-6">
                        
                        <!-- Info Servicio -->
                        <div class="space-y-1.5 flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="font-bold text-base text-slate-900" x-text="service.name"></h3>
                                
                                <!-- Badge Estado Operativo -->
                                <span 
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': service.status === 'online',
                                        'bg-amber-100 text-amber-800': service.status === 'degraded',
                                        'bg-rose-100 text-rose-800': service.status === 'offline'
                                    }"
                                    x-text="service.status === 'online' ? '● En Línea' : (service.status === 'degraded' ? '▲ Degradado' : '✖ Caído')"
                                ></span>

                                <!-- Badge Clasificación SLA -->
                                <template x-if="service.sla">
                                    <span 
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-300': service.sla.status === 'Cumple',
                                            'bg-amber-50 text-amber-700 border border-amber-300': service.sla.status === 'En riesgo',
                                            'bg-rose-50 text-rose-700 border border-rose-300': service.sla.status === 'Incumple'
                                        }"
                                        x-text="`SLA: ${service.sla.status}`"
                                    ></span>
                                </template>
                            </div>

                            <p class="text-xs text-slate-400" x-text="service.sla ? service.sla.message : ''"></p>
                        </div>

                        <!-- Métricas SLA y Presupuesto de Error -->
                        <template x-if="service.sla">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-left sm:text-right shrink-0">
                                <div>
                                    <span class="block text-[10px] font-bold uppercase text-slate-400">SLI Disponibilidad</span>
                                    <span class="font-black text-sm text-slate-800" x-text="`${service.sla.uptime_sli}%`"></span>
                                    <span class="block text-[10px] text-slate-400" x-text="`Meta: ${service.sla.slo_availability_target}%`"></span>
                                </div>

                                <div>
                                    <span class="block text-[10px] font-bold uppercase text-slate-400">Presupuesto Error</span>
                                    <span 
                                        class="font-black text-sm"
                                        :class="{
                                            'text-emerald-600': service.sla.error_budget_percentage > 25,
                                            'text-amber-600': service.sla.error_budget_percentage <= 25 && service.sla.error_budget_percentage > 0,
                                            'text-rose-600': service.sla.error_budget_percentage <= 0
                                        }"
                                        x-text="`${service.sla.error_budget_percentage}%`"
                                    ></span>
                                    <span class="block text-[10px] text-slate-400">Reserva de fallo</span>
                                </div>

                                <div>
                                    <span class="block text-[10px] font-bold uppercase text-slate-400">Latencia / SSL</span>
                                    <span class="font-bold text-sm text-slate-800" x-text="`${service.sla.avg_latency_ms} ms`"></span>
                                    <span class="block text-[10px] text-slate-400" x-text="`SSL: ${service.ssl_status}`"></span>
                                </div>
                            </div>
                        </template>

                    </div>
                </template>

                <template x-if="data && data.services.length === 0">
                    <div class="p-12 text-center text-slate-400 text-sm">
                        No hay servicios activos registrados en el monitor.
                    </div>
                </template>
            </div>
        </div>

        <!-- Bitácora de Incidentes Recientes -->
        <template x-if="data && (data.active_incidents.length > 0 || data.resolved_incidents.length > 0)">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="font-extrabold text-base text-slate-900">Historial de Incidentes Recientes</h3>

                <div class="space-y-3">
                    <template x-for="inc in data.active_incidents" :key="inc.id">
                        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-center justify-between">
                            <div>
                                <span class="font-bold" x-text="`[ACTIVO - ${inc.incident_type}] ${inc.service_name}`"></span>
                                <p class="text-rose-700 mt-0.5" x-text="inc.details"></p>
                            </div>
                            <span class="text-[11px] text-rose-500 font-mono" x-text="new Date(inc.started_at).toLocaleString()"></span>
                        </div>
                    </template>

                    <template x-for="inc in data.resolved_incidents" :key="inc.id">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-xs flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-900" x-text="`[RESUELTO - ${inc.incident_type}] ${inc.service_name}`"></span>
                                <p class="text-slate-500 mt-0.5" x-text="`Duración: ${inc.duration_seconds ? inc.duration_seconds + 's' : 'N/D'}`"></p>
                            </div>
                            <span class="text-[11px] text-slate-400 font-mono" x-text="new Date(inc.resolved_at).toLocaleString()"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>

    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-400">
        <p>&copy; {{ date('Y') }} UpTracker - Sistema de Monitoreo de Endpoints & Análisis de Niveles de Servicio (SLA/SLO).</p>
    </footer>

</body>
</html>
