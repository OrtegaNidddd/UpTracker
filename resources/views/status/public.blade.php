<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estado de Servicios y Salud del Sistema - {{ config('app.name', 'UpTracker') }}</title>
    <meta name="description" content="Página de estado oficial y telemetría pública en tiempo real de la infraestructura y servicios de UpTracker.">
    <meta name="robots" content="index, follow">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-brand-dark min-h-full flex flex-col justify-between selection:bg-brand-primary selection:text-white">
    <!-- Recarga automática silenciosa cada 1 minuto (sin contador visual) -->
    <script>
        setTimeout(() => window.location.reload(), 60000);
    </script>

    <!-- Header Público -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary transition group-hover:scale-105">
                        <x-application-logo class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="font-extrabold text-base tracking-tight text-brand-dark">UpTracker</span>
                        <span class="block text-[10px] uppercase font-semibold text-slate-400">Página de Estado Oficial</span>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-brand-primary hover:bg-blue-600 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    <span>Acceder</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-8">

            <!-- 1. Hero Status Banner -->
            @php
                $statusConfig = match ($status) {
                    'Operational' => [
                        'bg' => 'bg-emerald-500',
                        'border' => 'border-emerald-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                        'title' => 'Todos los Sistemas Operando con Normalidad',
                        'subtitle' => 'Nuestras APIs, bases de datos y microservicios están respondiendo dentro de los parámetros óptimos.',
                    ],
                    'Major_Outage' => [
                        'bg' => 'bg-rose-600',
                        'border' => 'border-rose-700',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
                        'title' => 'Interrupción Crítica en Sistemas Principales',
                        'subtitle' => 'El equipo de operaciones está investigando la degradación masiva para restablecer la conectividad.',
                    ],
                    'Partial_Outage', 'Degraded_Performance' => [
                        'bg' => 'bg-amber-500',
                        'border' => 'border-amber-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                        'title' => 'Rendimiento Degradado o Caída Parcial',
                        'subtitle' => 'Se detectaron latencias anómalas o caídas puntuales en algunos endpoints supervisados.',
                    ],
                    default => [
                        'bg' => 'bg-slate-700',
                        'border' => 'border-slate-800',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        'title' => 'Sin Servicios Monitoreados',
                        'subtitle' => 'Actualmente no hay endpoints públicos activos en este instante.',
                    ],
                };
            @endphp

            <div class="{{ $statusConfig['bg'] }} text-white rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-200/50 flex flex-col sm:flex-row sm:items-center justify-between gap-6 relative overflow-hidden">
                <!-- Decoración -->
                <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="space-y-2 relative z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-xs font-semibold backdrop-blur-xs">
                        <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                        Diagnóstico Global
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight leading-tight">
                        {{ $statusConfig['title'] }}
                    </h1>
                    <p class="text-xs sm:text-sm text-white/90 max-w-xl leading-relaxed">
                        {{ $statusConfig['subtitle'] }}
                    </p>
                </div>

                <div class="shrink-0 bg-white/10 border border-white/20 rounded-2xl p-4 text-center backdrop-blur-xs relative z-10 min-w-[140px]">
                    <span class="text-[11px] uppercase tracking-wider font-semibold text-white/80">Servicios Online</span>
                    <p class="text-3xl font-black mt-1">{{ $online_count }} / {{ $total_monitored }}</p>
                    <span class="text-[10px] text-white/70 block mt-0.5">Disponibilidad total</span>
                </div>
            </div>

            <!-- 2. Lista de Componentes / Servicios Públicos -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-lg text-brand-dark">Componentes de Infraestructura</h2>
                        <p class="text-xs text-brand-muted">Métricas consolidadas de disponibilidad calculadas en las últimas 24 horas</p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600">
                        {{ count($services) }} Componentes
                    </span>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($services as $service)
                        <div class="p-5 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/60 transition">
                            <div class="space-y-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="font-bold text-sm text-brand-dark">{{ $service['name'] }}</h3>
                                    @if($service['status'] === 'online')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Operativo
                                        </span>
                                    @elseif($service['status'] === 'degraded')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Lento
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span> Caído
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-400">
                                    <span>Certificado SSL: <strong class="text-slate-600">{{ $service['ssl_status'] }}</strong></span>
                                    <span>•</span>
                                    <span>Último sondeo: {{ $service['last_checked_at'] ? \Carbon\Carbon::parse($service['last_checked_at'])->diffForHumans() : 'Reciente' }}</span>
                                </div>
                            </div>

                            <!-- Barra de Uptime 24h -->
                            <div class="w-full sm:w-64 space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-400 font-medium">Uptime 24h</span>
                                    <span class="font-bold text-slate-700">{{ $service['uptime_24h'] }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full {{ $service['uptime_24h'] >= 99 ? 'bg-emerald-500' : ($service['uptime_24h'] >= 95 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ min(100, max(0, $service['uptime_24h'])) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400 text-xs">
                            No hay servicios registrados en la plataforma.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- 3. Incidentes Activos y Recientes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Incidentes Activos -->
                <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="font-bold text-base text-brand-dark flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ count($active_incidents) > 0 ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500' }}"></span>
                            Incidentes Activos
                        </h3>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ count($active_incidents) > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
                            {{ count($active_incidents) }} en curso
                        </span>
                    </div>

                    @forelse ($active_incidents as $inc)
                        <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200/80 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-rose-800">{{ $inc['service_name'] }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-rose-600 text-white tracking-wider animate-pulse">
                                    {{ $inc['incident_type'] }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-700 font-medium leading-relaxed">
                                {{ $inc['details'] ?? 'Fallo de conectividad o latencia excedida.' }}
                            </p>
                            <span class="text-[11px] text-slate-400 block pt-1 border-t border-rose-200/50">
                                Iniciado: {{ \Carbon\Carbon::parse($inc['started_at'])->diffForHumans() }}
                            </span>
                        </div>
                    @empty
                        <div class="py-10 text-center space-y-2">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h4 class="font-bold text-xs text-slate-700">Sin incidentes abiertos</h4>
                            <p class="text-[11px] text-slate-400">Todos los endpoints están operando con normalidad.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Historial de Incidentes Resueltos -->
                <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="font-bold text-base text-brand-dark">Incidentes Resueltos</h3>
                        <span class="text-xs text-slate-400">Últimos eventos</span>
                    </div>

                    @forelse ($resolved_incidents as $inc)
                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-slate-800">{{ $inc['service_name'] }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                    Resuelto
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-slate-500">
                                <span>Duración: <strong>{{ $inc['duration_seconds'] ? gmdate("H:i:s", $inc['duration_seconds']) : 'Pocos segundos' }}</strong></span>
                                <span>{{ \Carbon\Carbon::parse($inc['resolved_at'])->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center space-y-2">
                            <h4 class="font-bold text-xs text-slate-700">Historial limpio</h4>
                            <p class="text-[11px] text-slate-400">No hay registro de caídas recientes.</p>
                        </div>
                    @endforelse
                </div>

            </div>

        </div>
    </main>

    <!-- Footer Corporativo Público -->
    <x-corporate-footer />

    <!-- Banner de Cookies y Ley 1581 -->
    <x-cookie-banner />

</body>
</html>
