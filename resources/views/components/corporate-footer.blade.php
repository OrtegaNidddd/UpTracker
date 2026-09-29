<footer class="mt-auto border-t border-slate-200 bg-white/80 backdrop-blur-sm text-brand-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Columna 1: Marca & Misión -->
            <div class="space-y-3 md:col-span-1">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary font-bold">
                        <x-application-logo class="w-5 h-5" />
                    </div>
                    <span class="font-extrabold text-base tracking-tight text-brand-dark">UpTracker</span>
                </div>
                <p class="text-xs text-brand-muted leading-relaxed">
                    Plataforma inteligente de monitoreo de disponibilidad, latencia y certificados SSL en tiempo real para microservicios y APIs críticas.
                </p>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-semibold text-emerald-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <a href="{{ route('status.public') }}" class="hover:underline">Estado Público Global</a>
                </div>
            </div>

            <!-- Columna 2: Navegación & Módulos -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Plataforma</h4>
                <ul class="space-y-2 text-xs text-brand-muted">
                    <li><a href="{{ route('dashboard') }}" class="hover:text-brand-primary transition">Dashboard Telemetría</a></li>
                    <li><a href="{{ route('services.index') }}" class="hover:text-brand-primary transition">Gestión de Endpoints</a></li>
                    <li><a href="{{ route('notification-channels.index') }}" class="hover:text-brand-primary transition">Canales de Alerta (Discord / Telegram)</a></li>
                    <li><a href="{{ route('status.public') }}" class="hover:text-brand-primary transition">Página de Estado Pública</a></li>
                </ul>
            </div>

            <!-- Columna 3: Soporte y Atención -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Soporte Técnico</h4>
                <ul class="space-y-2 text-xs text-brand-muted">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <a href="mailto:soporte@uptracker.dev" class="hover:text-brand-primary transition font-mono">soporte@uptracker.dev</a>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Lun - Vie: 8:00 AM - 6:00 PM (COT)</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Bogotá D.C., Colombia</span>
                    </li>
                </ul>
            </div>

            <!-- Columna 4: Cumplimiento Legal (Ley 1581 / 2012) -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Legal y Conformidad</h4>
                <ul class="space-y-2 text-xs text-brand-muted">
                    <li><span class="font-medium text-slate-700">Ley 1581 de 2012</span> (Habeas Data)</li>
                    <li><a href="#terminos" class="hover:text-brand-primary transition">Términos del Servicio</a></li>
                    <li><a href="#privacidad" class="hover:text-brand-primary transition">Política de Tratamiento de Datos</a></li>
                    <li><a href="#seguridad" class="hover:text-brand-primary transition">Compromiso de Seguridad & SLA</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-brand-muted">
            <p>&copy; {{ date('Y') }} UpTracker. Todos los derechos reservados. Desarrollado con Laravel, Alpine.js y Reverb.</p>
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    SLA Objetivo 99.9%
                </span>
                <span>•</span>
                <span>Versión v1.2.0</span>
            </div>
        </div>
    </div>
</footer>
