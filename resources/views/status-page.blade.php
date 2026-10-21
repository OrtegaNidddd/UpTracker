<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página de Estado y Cumplimiento SLA - UpTracker</title>
    <meta name="description" content="Página oficial de estado, telemetría y métricas de nivel de servicio (SLA/SLO) en tiempo real de UpTracker.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <style>
        [x-cloak] { display: none !important; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-800 min-h-full flex flex-col justify-between selection:bg-brand-primary selection:text-white"
    x-data="{
        loading: true,
        data: null,
        lastUpdated: '',
        errorMessage: null,
        fetchStatus() {
            this.loading = true;
            this.errorMessage = null;
            fetch('{{ route('status.public') }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Respuesta HTTP no satisfactoria: ' + r.status);
                    }
                    return r.json();
                })
                .then(json => {
                    this.data = json;
                    this.lastUpdated = new Date().toLocaleTimeString();
                    this.loading = false;
                })
                .catch(err => {
                    console.error('Error cargando estado:', err);
                    this.errorMessage = 'No fue posible sincronizar el estado del sistema en tiempo real. Por favor intenta nuevamente.';
                    this.loading = false;
                });
        }
    }"
    x-init="fetchStatus(); setInterval(() => fetchStatus(), 30000);">

    <!-- Header Público Corporativo -->
    <header class="border-b border-slate-200/80 bg-white/95 backdrop-blur-md shadow-xs sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
            
            <!-- Logo & Identidad de Marca -->
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-primary to-blue-500 flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform" aria-hidden="true">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <span class="font-black text-xl text-slate-900 tracking-tight group-hover:text-brand-primary transition-colors">UpTracker</span>
                        <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Página de Estado Pública &amp; SLA</span>
                    </div>
                </a>
            </div>

            <!-- Acciones de Navegación y Control -->
            <div class="flex items-center gap-2.5 sm:gap-3">
                <!-- Indicador de Sincronización en Vivo -->
                <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100/80 border border-slate-200/60 text-[11px] font-semibold text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>En Vivo (30s)</span>
                </div>

                <!-- Botón Manual de Actualización -->
                <button 
                    @click="fetchStatus()" 
                    :disabled="loading"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl shadow-xs transition hover:border-slate-300 disabled:opacity-50 cursor-pointer active:scale-95"
                    title="Actualizar telemetría de servicios"
                >
                    <svg class="w-3.5 h-3.5 text-slate-500" :class="loading ? 'animate-spin text-brand-primary' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="loading ? 'Actualizando...' : 'Actualizar'"></span>
                </button>

                <!-- Acceso a Sesión / Panel -->
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-brand-primary hover:bg-blue-700 rounded-xl shadow-sm transition transform active:scale-95">
                        <span>Ir al Panel</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                        <span>Iniciar Sesión</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex-1 w-full space-y-6">

        <!-- Mensaje de Error en caso de fallo de red -->
        <template x-if="errorMessage">
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-xs font-medium" x-text="errorMessage"></p>
                </div>
                <button @click="fetchStatus()" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shrink-0 cursor-pointer">
                    Reintentar sincronización
                </button>
            </div>
        </template>

        <!-- Skeleton Loader inicial -->
        <div x-show="loading && !data" class="space-y-6 animate-pulse">
            <div class="h-28 bg-slate-200/70 rounded-3xl"></div>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="h-24 bg-slate-200/70 rounded-2xl"></div>
                <div class="h-24 bg-slate-200/70 rounded-2xl"></div>
                <div class="h-24 bg-slate-200/70 rounded-2xl"></div>
                <div class="h-24 bg-slate-200/70 rounded-2xl"></div>
            </div>
            <div class="h-64 bg-slate-200/70 rounded-3xl"></div>
        </div>

        <!-- 1. Banner Estado Global -->
        <template x-if="data">
            <div 
                class="p-6 sm:p-7 rounded-3xl shadow-sm border transition-all relative overflow-hidden"
                :class="{
                    'bg-emerald-500 text-white border-emerald-600 shadow-emerald-500/10': data.status === 'Operational',
                    'bg-amber-500 text-white border-amber-600 shadow-amber-500/10': data.status === 'Degraded_Performance' || data.status === 'Partial_Outage',
                    'bg-rose-600 text-white border-rose-700 shadow-rose-600/10': data.status === 'Major_Outage',
                    'bg-slate-800 text-white border-slate-700 shadow-slate-900/10': data.status === 'No_Services'
                }">
                <!-- Halo decorativo -->
                <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 shadow-inner">
                            <template x-if="data.status === 'Operational'">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="data.status === 'No_Services'">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="data.status !== 'Operational' && data.status !== 'No_Services'">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </template>
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-white/20 text-[11px] font-bold uppercase tracking-wider backdrop-blur-xs mb-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                Diagnóstico Oficial
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight" x-text="data.summary"></h1>
                            <p class="text-xs text-white/80 mt-1">
                                Última comprobación: <span class="font-mono font-bold" x-text="lastUpdated"></span> &bull; Actualización automática continua cada 30 segundos
                            </p>
                        </div>
                    </div>

                    <div class="sm:text-right sm:border-l sm:border-white/20 sm:pl-6 shrink-0">
                        <span class="text-xs uppercase font-bold tracking-wider text-white/80 block">Servicios Operativos</span>
                        <p class="text-3xl font-black mt-0.5" x-text="`${data.online_count} / ${data.total_monitored}`"></p>
                        <span class="text-[11px] text-white/70 block mt-0.5" x-text="data.total_monitored > 0 ? `${Math.round((data.online_count / data.total_monitored) * 100)}% de infraestructura activa` : 'Sin endpoints públicos'"></span>
                    </div>
                </div>
            </div>
        </template>

        <!-- 2. Tarjetas Resumen de Nivel de Servicio (SLA / SLO) -->
        <template x-if="data && data.sla_summary">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Cumplimiento Global SLO</span>
                    <p class="text-2xl font-black text-brand-primary mt-1" x-text="`${data.sla_summary.overall_compliance_rate}%`"></p>
                    <p class="text-[11px] text-slate-400 mt-1">Meta agregada de servicios</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Servicios Cumplen</span>
                    <p class="text-2xl font-black text-emerald-600 mt-1" x-text="data.sla_summary.compliant_count"></p>
                    <p class="text-[11px] text-slate-400 mt-1">SLI &ge; SLO pactado</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-600">Servicios En Riesgo</span>
                    <p class="text-2xl font-black text-amber-600 mt-1" x-text="data.sla_summary.at_risk_count"></p>
                    <p class="text-[11px] text-slate-400 mt-1">Presupuesto de error bajo (&le; 25%)</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Servicios Incumplen</span>
                    <p class="text-2xl font-black text-rose-600 mt-1" x-text="data.sla_summary.breached_count"></p>
                    <p class="text-[11px] text-slate-400 mt-1">SLI inferior al objetivo contractual</p>
                </div>
            </div>
        </template>

        <!-- 3. Lista de Servicios y Estado de SLA -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Estado de Servicios y Cumplimiento SLA</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Métricas de disponibilidad, latencia y objetivos pactados (SLO).</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200/60">
                        Ventana de Auditoría: 30 Días
                    </span>
                </div>
            </div>

            <!-- Loader dentro de la tabla si está actualizando -->
            <div x-show="loading && !data" class="p-16 text-center text-slate-400 text-sm">
                <svg class="w-8 h-8 animate-spin mx-auto text-brand-primary mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Sincronizando estado del sistema y telemetría SLA...
            </div>

            <!-- Contenido cuando ya hay datos -->
            <div x-show="data" class="divide-y divide-slate-100">
                <template x-for="service in (data ? data.services : [])" :key="service.id">
                    <div class="p-6 hover:bg-slate-50/60 transition flex flex-col md:flex-row md:items-center justify-between gap-6">
                        
                        <!-- Info Servicio -->
                        <div class="space-y-1.5 flex-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <h3 class="font-bold text-base text-slate-900" x-text="service.name"></h3>
                                
                                <!-- Badge Estado Operativo -->
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 border border-emerald-200': service.status === 'online',
                                        'bg-amber-100 text-amber-800 border border-amber-200': service.status === 'degraded',
                                        'bg-rose-100 text-rose-800 border border-rose-200': service.status === 'offline'
                                    }"
                                    x-text="service.status === 'online' ? '● En Línea' : (service.status === 'degraded' ? '▲ Degradado' : '✖ Caído')"
                                ></span>

                                <!-- Badge Clasificación SLA -->
                                <template x-if="service.sla">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-300': service.sla.status === 'Cumple',
                                            'bg-amber-50 text-amber-700 border border-amber-300': service.sla.status === 'En riesgo',
                                            'bg-rose-50 text-rose-700 border border-rose-300': service.sla.status === 'Incumple'
                                        }"
                                        x-text="`SLA: ${service.sla.status}`"
                                    ></span>
                                </template>
                            </div>

                            <p class="text-xs text-slate-500" x-text="service.sla ? service.sla.message : 'Sondeo activo'"></p>
                        </div>

                        <!-- Métricas SLA y Presupuesto de Error -->
                        <template x-if="service.sla">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-5 text-left sm:text-right shrink-0">
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

                <!-- Estado cuando no hay servicios -->
                <template x-if="data && data.services.length === 0">
                    <div class="p-16 text-center space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <h4 class="font-bold text-base text-slate-800">No hay endpoints activos registrados actualmente</h4>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            Los servicios aparecerán automáticamente una vez registrados y activados en el panel de control.
                        </p>
                        @auth
                            <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-primary text-white text-xs font-semibold rounded-xl shadow-sm hover:bg-blue-700 transition mt-2">
                                Registrar nuevo endpoint
                            </a>
                        @endauth
                    </div>
                </template>
            </div>
        </div>

        <!-- 4. Bitácora de Incidentes Recientes -->
        <template x-if="data && (data.active_incidents.length > 0 || data.resolved_incidents.length > 0)">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-base text-slate-900">Historial y Bitácora de Incidentes Recientes</h3>
                    <span class="text-xs text-slate-400 font-medium">Últimos eventos reportados</span>
                </div>

                <div class="space-y-3">
                    <template x-for="inc in data.active_incidents" :key="inc.id">
                        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-xs">
                            <div>
                                <span class="font-bold text-rose-900" x-text="`[ACTIVO - ${inc.incident_type}] ${inc.service_name}`"></span>
                                <p class="text-rose-700 mt-0.5" x-text="inc.details"></p>
                            </div>
                            <span class="text-[11px] text-rose-500 font-mono shrink-0" x-text="new Date(inc.started_at).toLocaleString()"></span>
                        </div>
                    </template>

                    <template x-for="inc in data.resolved_incidents" :key="inc.id">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <span class="font-bold text-slate-900" x-text="`[RESUELTO - ${inc.incident_type}] ${inc.service_name}`"></span>
                                <p class="text-slate-500 mt-0.5" x-text="`Duración: ${inc.duration_seconds ? inc.duration_seconds + 's' : 'Pocos segundos'}`"></p>
                            </div>
                            <span class="text-[11px] text-slate-400 font-mono shrink-0" x-text="new Date(inc.resolved_at).toLocaleString()"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>

    </main>

    <!-- Footer Corporativo Público Oficial -->
    <x-corporate-footer />

    <!-- Banner y Modal de Consentimiento de Políticas y Cookies (Ley 1581) -->
    <x-cookie-banner />

</body>
</html>
