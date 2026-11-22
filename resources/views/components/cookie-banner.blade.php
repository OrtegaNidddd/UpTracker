<div 
    x-data="{ 
        bannerVisible: false,
        modalOpen: false,
        activeTab: 'cookies',
        telemetry: true,
        preferences: true,

        init() {
            const consent = localStorage.getItem('uptracker_cookie_consent');
            if (!consent) {
                setTimeout(() => { this.bannerVisible = true; }, 700);
            } else if (consent === 'essential' || consent === 'rejected') {
                this.telemetry = false;
                this.preferences = false;
            } else if (consent !== 'all') {
                try {
                    const parsed = JSON.parse(consent);
                    this.telemetry = !!parsed.telemetry;
                    this.preferences = !!parsed.preferences;
                } catch (e) {
                    this.telemetry = true;
                    this.preferences = true;
                }
            }

            // Escuchar eventos globales para abrir modal desde cualquier enlace del footer o interfaz
            window.addEventListener('open-policy-modal', (e) => {
                this.activeTab = (e.detail && e.detail.tab) ? e.detail.tab : 'cookies';
                this.modalOpen = true;
            });

            window.addEventListener('open-cookie-banner', () => {
                this.bannerVisible = true;
            });
        },

        acceptAll() {
            localStorage.setItem('uptracker_cookie_consent', 'all');
            this.telemetry = true;
            this.preferences = true;
            this.bannerVisible = false;
            this.modalOpen = false;
            this.notify('Consentimiento guardado: Todas las políticas y cookies aceptadas.');
        },

        acceptEssentialOnly() {
            localStorage.setItem('uptracker_cookie_consent', 'essential');
            this.telemetry = false;
            this.preferences = false;
            this.bannerVisible = false;
            this.modalOpen = false;
            this.notify('Consentimiento guardado: Solo cookies técnicas esenciales activas.');
        },

        rejectAll() {
            localStorage.setItem('uptracker_cookie_consent', 'rejected');
            this.telemetry = false;
            this.preferences = false;
            this.bannerVisible = false;
            this.modalOpen = false;
            this.notify('Cookies no esenciales rechazadas conforme a la Ley 1581 de 2012.');
        },

        saveCustomPreferences() {
            const prefs = {
                essential: true,
                telemetry: this.telemetry,
                preferences: this.preferences,
                updated_at: new Date().toISOString()
            };
            localStorage.setItem('uptracker_cookie_consent', JSON.stringify(prefs));
            this.bannerVisible = false;
            this.modalOpen = false;
            this.notify('Preferencias de privacidad personalizadas guardadas con éxito.');
        },

        openModal(tab = 'cookies') {
            this.activeTab = tab;
            this.modalOpen = true;
        },

        notify(message) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message: message, type: 'info' }
            }));
        }
    }"
    class="relative"
>
    <!-- 1. Banner Flotante de Consentimiento (Cookie Consent Banner) -->
    <div 
        x-show="bannerVisible"
        x-cloak
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 translate-y-8"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-8"
        class="fixed bottom-4 inset-x-4 sm:left-6 sm:bottom-6 sm:right-auto sm:max-w-lg z-50"
    >
        <div class="bg-white/95 backdrop-blur-xl border border-slate-200/90 rounded-2xl shadow-2xl p-5 text-slate-800 ring-1 ring-black/5">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 text-brand-primary flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>

                <div class="flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="font-bold text-sm text-slate-900 flex items-center gap-1.5">
                            Políticas y Privacidad
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-blue-50 text-brand-primary border border-blue-200">Ley 1581 / 2012</span>
                        </h4>
                        <button 
                            @click="bannerVisible = false"
                            class="text-slate-400 hover:text-slate-600 p-0.5 rounded-md transition"
                            title="Ocultar banner temporalmente"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                        Utilizamos cookies técnicas necesarias para el funcionamiento seguro de tu sesión, así como cookies de telemetría para monitoreo de endpoints bajo la <strong>Ley 1581 de 2012 (Habeas Data)</strong>. Puedes aceptar todas, rechazarlas o ver cuáles son las políticas aplicadas.
                    </p>

                    <!-- Acciones del Banner: Aceptar, Solo Esenciales, Rechazar y Ver Políticas -->
                    <div class="mt-3.5 flex flex-wrap items-center gap-2">
                        <button 
                            @click="acceptAll()"
                            class="px-3.5 py-1.5 bg-brand-primary hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition transform active:scale-95"
                        >
                            Aceptar
                        </button>
                        <button 
                            @click="acceptEssentialOnly()"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition active:scale-95"
                        >
                            Solo Esenciales
                        </button>
                        <button 
                            @click="rejectAll()"
                            class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-rose-600 hover:text-rose-700 text-xs font-semibold rounded-lg transition active:scale-95"
                        >
                            Rechazar
                        </button>
                        <button 
                            type="button"
                            @click="openModal('cookies')"
                            class="text-xs text-brand-primary hover:text-blue-800 font-semibold underline inline-flex items-center gap-1 transition ml-auto"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Ver Políticas</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Modal Detallado de Políticas y Cookies (¿Cuáles son las políticas?) -->
    <div 
        x-show="modalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="policy-modal-title" 
        role="dialog" 
        aria-modal="true"
        @keydown.escape.window="modalOpen = false"
    >
        <!-- Backdrop oscuro con desenfoque -->
        <div 
            x-show="modalOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="modalOpen = false"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        ></div>

        <!-- Contenedor del Modal -->
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div 
                x-show="modalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-slate-200/80 flex flex-col max-h-[90vh]"
            >
                <!-- Cabecera del Modal -->
                <div class="p-6 border-b border-slate-100 flex items-start justify-between bg-gradient-to-r from-slate-50 to-white shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-brand-primary text-white flex items-center justify-center shadow-sm shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 id="policy-modal-title" class="text-lg font-extrabold text-slate-900 tracking-tight">
                                Centro de Políticas, Cookies y Tratamiento de Datos
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                UpTracker Monitoring Systems &bull; Cumplimiento normativo Ley 1581 de 2012 (Colombia)
                            </p>
                        </div>
                    </div>
                    <button 
                        @click="modalOpen = false" 
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                        title="Cerrar ventana"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Pestañas de Navegación del Centro de Políticas -->
                <div class="flex items-center gap-2 px-6 pt-3 border-b border-slate-100 bg-slate-50/50 shrink-0 overflow-x-auto text-xs font-semibold">
                    <button 
                        @click="activeTab = 'cookies'"
                        :class="activeTab === 'cookies' ? 'border-brand-primary text-brand-primary bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/60'"
                        class="px-4 py-2.5 rounded-t-xl border-b-2 transition flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        <span>1. Políticas de Cookies</span>
                    </button>
                    <button 
                        @click="activeTab = 'privacy'"
                        :class="activeTab === 'privacy' ? 'border-brand-primary text-brand-primary bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/60'"
                        class="px-4 py-2.5 rounded-t-xl border-b-2 transition flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>2. Tratamiento de Datos (Ley 1581)</span>
                    </button>
                    <button 
                        @click="activeTab = 'security'"
                        :class="activeTab === 'security' ? 'border-brand-primary text-brand-primary bg-white shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/60'"
                        class="px-4 py-2.5 rounded-t-xl border-b-2 transition flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>3. Seguridad & SLA</span>
                    </button>
                </div>

                <!-- Contenido Dinámico de las Pestañas (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1 text-slate-700 text-xs leading-relaxed">
                    
                    <!-- TAB 1: POLÍTICAS DE COOKIES (Desglose exhaustivo) -->
                    <div x-show="activeTab === 'cookies'" class="space-y-5">
                        <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-100 text-slate-800 space-y-1.5">
                            <h4 class="font-bold text-sm text-brand-primary flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                ¿Cuáles son las cookies y políticas que utilizamos en UpTracker?
                            </h4>
                            <p class="text-xs text-slate-600">
                                A continuación detallamos la totalidad de cookies y mecanismos de almacenamiento local que utiliza la plataforma, su finalidad técnica, duración y control de consentimiento:
                            </p>
                        </div>

                        <!-- Categoría 1: Cookies Esenciales -->
                        <div class="p-5 rounded-2xl border border-slate-200 bg-white shadow-xs space-y-3">
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                                        ✓
                                    </div>
                                    <div>
                                        <h5 class="font-extrabold text-slate-900 text-sm">1. Cookies Técnicas y de Sesión (Estrictamente Esenciales)</h5>
                                        <span class="text-[11px] text-slate-400">Requeridas para la seguridad, navegación y autenticación</span>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Siempre Activas
                                </span>
                            </div>
                            
                            <p class="text-xs text-slate-600">
                                Estas cookies son indispensables para que la plataforma funcione de manera segura. No almacenan información con fines publicitarios y no pueden desactivarse.
                            </p>

                            <div class="space-y-2 mt-2 bg-slate-50 p-3 rounded-xl border border-slate-100 text-[11px]">
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                                    <span class="font-mono font-bold text-slate-800">uptracker_session</span>
                                    <span class="text-slate-500">Mantiene la sesión de usuario autenticada de forma cifrada (Laravel).</span>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-1 pt-1.5 border-t border-slate-200/50">
                                    <span class="font-mono font-bold text-slate-800">XSRF-TOKEN</span>
                                    <span class="text-slate-500">Previene ataques de falsificación de solicitudes en sitios cruzados (CSRF).</span>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:justify-between gap-1 pt-1.5 border-t border-slate-200/50">
                                    <span class="font-mono font-bold text-slate-800">uptracker_cookie_consent</span>
                                    <span class="text-slate-500">Guarda tus preferencias de aceptación/rechazo para no preguntar repetidamente.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Categoría 2: Cookies de Telemetría -->
                        <div class="p-5 rounded-2xl border border-slate-200 bg-white shadow-xs space-y-3">
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-brand-primary flex items-center justify-center font-bold text-xs shrink-0">
                                        ⚡
                                    </div>
                                    <div>
                                        <h5 class="font-extrabold text-slate-900 text-sm">2. Cookies de Telemetría y Rendimiento de Red</h5>
                                        <span class="text-[11px] text-slate-400">Medición de latencia de endpoints y tiempos de sondeo en navegador</span>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="telemetry" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
                                </label>
                            </div>
                            
                            <p class="text-xs text-slate-600">
                                Recopilan datos anónimos de tiempo de respuesta de red cuando consultas la página de estado pública y los dashboards, permitiendo calcular el <em>Error Budget</em> y detectar latencias anómalas en tu región.
                            </p>

                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-[11px]">
                                <span class="font-mono font-bold text-slate-800">uptracker_telemetry_metrics</span>: Latencia promedio de peticiones públicas y estadísticas agregadas (Retención: 30 días).
                            </div>
                        </div>

                        <!-- Categoría 3: Cookies de Preferencias -->
                        <div class="p-5 rounded-2xl border border-slate-200 bg-white shadow-xs space-y-3">
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs shrink-0">
                                        ⚙
                                    </div>
                                    <div>
                                        <h5 class="font-extrabold text-slate-900 text-sm">3. Cookies de Preferencias y Visualización</h5>
                                        <span class="text-[11px] text-slate-400">Recordar filtros de auditoría SLA y personalización de panel</span>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="preferences" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
                                </label>
                            </div>
                            
                            <p class="text-xs text-slate-600">
                                Permiten guardar la vista preferida (ventana de auditoría de 30 días, orden de servicios, columnas visibles) sin necesidad de reconfigurarla en cada visita.
                            </p>
                        </div>
                    </div>

                    <!-- TAB 2: POLÍTICA DE TRATAMIENTO DE DATOS PERSONALES (Ley 1581 / Habeas Data) -->
                    <div x-show="activeTab === 'privacy'" class="space-y-4">
                        <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 text-slate-800 space-y-1">
                            <h4 class="font-bold text-sm text-emerald-800 flex items-center gap-1.5">
                                ⚖ Política de Tratamiento de Información y Datos Personales
                            </h4>
                            <p class="text-xs text-slate-600">
                                Marco legal aplicable: <strong>Ley Estatutaria 1581 de 2012</strong> y Decreto Reglamentario 1377 de 2013 de la República de Colombia (Habeas Data).
                            </p>
                        </div>

                        <div class="space-y-3 p-4 bg-white rounded-2xl border border-slate-200">
                            <h5 class="font-bold text-slate-900 text-xs uppercase tracking-wider">1. Responsable del Tratamiento</h5>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                <strong>UpTracker Monitoring Systems</strong>, con domicilio en Bogotá D.C., Colombia, es el responsable del tratamiento de los datos personales recolectados a través de esta plataforma. Canal de contacto oficial: <a href="mailto:soporte@uptracker.dev" class="text-brand-primary font-mono font-semibold underline">soporte@uptracker.dev</a>.
                            </p>
                        </div>

                        <div class="space-y-3 p-4 bg-white rounded-2xl border border-slate-200">
                            <h5 class="font-bold text-slate-900 text-xs uppercase tracking-wider">2. Finalidades del Tratamiento</h5>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Los datos suministrados por los usuarios (como nombre, correo electrónico y credenciales) se tratarán única y exclusivamente para:
                            </p>
                            <ul class="list-disc list-inside space-y-1 text-xs text-slate-600 pl-2">
                                <li>Gestionar la cuenta de usuario y validar el acceso al panel de control de telemetría.</li>
                                <li>Emitir notificaciones y alertas inmediatas ante caídas (Down) o degradaciones de servicios mediante Discord, Telegram y Correo.</li>
                                <li>Generar métricas de cumplimiento de Nivel de Servicio (SLA) y cálculo de Error Budget.</li>
                                <li>Garantizar la trazabilidad y seguridad perimetral de los endpoints monitoreados.</li>
                            </ul>
                            <p class="text-xs font-semibold text-slate-700 mt-2 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                🛡 Compromiso de No Comercialización: UpTracker bajo ninguna circunstancia vende, cede ni transfiere bases de datos o información personal a terceros.
                            </p>
                        </div>

                        <div class="space-y-3 p-4 bg-white rounded-2xl border border-slate-200">
                            <h5 class="font-bold text-slate-900 text-xs uppercase tracking-wider">3. Derechos del Titular (Habeas Data)</h5>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Conforme al artículo 8 de la Ley 1581 de 2012, usted tiene derecho a:
                            </p>
                            <ul class="list-disc list-inside space-y-1 text-xs text-slate-600 pl-2">
                                <li><strong>Conocer, actualizar y rectificar</strong> sus datos personales en cualquier momento.</li>
                                <li><strong>Solicitar prueba</strong> de la autorización otorgada.</li>
                                <li><strong>Revocar la autorización</strong> o solicitar la supresión del dato cuando no se respeten los principios legales.</li>
                                <li><strong>Presentar quejas</strong> ante la Superintendencia de Industria y Comercio (SIC).</li>
                            </ul>
                            <p class="text-xs text-slate-500 pt-1">
                                Las peticiones dirigidas a <span class="font-mono text-slate-700">soporte@uptracker.dev</span> serán resueltas en un término máximo de <strong>10 días hábiles</strong>.
                            </p>
                        </div>
                    </div>

                    <!-- TAB 3: SEGURIDAD & COMPROMISO SLA -->
                    <div x-show="activeTab === 'security'" class="space-y-4">
                        <div class="p-4 rounded-2xl bg-slate-100 border border-slate-200 text-slate-800 space-y-1">
                            <h4 class="font-bold text-sm text-slate-900 flex items-center gap-1.5">
                                🔒 Arquitectura de Seguridad y Acuerdos de Nivel de Servicio (SLA)
                            </h4>
                            <p class="text-xs text-slate-600">
                                Estándares de protección criptográfica y confiabilidad operativa implementados en UpTracker.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 bg-white rounded-2xl border border-slate-200 space-y-2">
                                <span class="text-xs font-bold uppercase text-brand-primary">Cifrado de Extremo a Extremo</span>
                                <p class="text-xs text-slate-600">
                                    Todas las conexiones web y llamadas a la API se canalizan a través de certificados TLS 1.3 con HTTPS forzado. Las contraseñas de usuarios están protegidas mediante hashing Bcrypt seguro.
                                </p>
                            </div>
                            <div class="p-4 bg-white rounded-2xl border border-slate-200 space-y-2">
                                <span class="text-xs font-bold uppercase text-emerald-600">Objetivo SLA 99.9%</span>
                                <p class="text-xs text-slate-600">
                                    Compromiso de disponibilidad de la plataforma con auditoría en ventana rodante de 30 días, monitoreo periódico de certificados SSL y cálculo automatizado de Error Budget.
                                </p>
                            </div>
                        </div>

                        <div class="p-4 bg-white rounded-2xl border border-slate-200 space-y-2">
                            <span class="text-xs font-bold uppercase text-slate-800">Rate Limiting y Protección contra Abuso</span>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Los endpoints públicos y de autenticación cuentan con aceleradores de limitación de tasa (Rate Limiting de 30 y 10 peticiones por minuto respectivamente) para mitigar ataques de denegación de servicio (DDoS) y fuerza bruta.
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Barra de Acciones del Modal: Aceptar, Solo Esenciales, Rechazar, Guardar Personalizado -->
                <div class="p-5 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button 
                            type="button"
                            @click="acceptAll()"
                            class="flex-1 sm:flex-initial px-4 py-2 bg-brand-primary hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition active:scale-95"
                        >
                            Aceptar Todas
                        </button>
                        <button 
                            type="button"
                            @click="acceptEssentialOnly()"
                            class="flex-1 sm:flex-initial px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-xl transition active:scale-95"
                        >
                            Solo Esenciales
                        </button>
                        <button 
                            type="button"
                            @click="rejectAll()"
                            class="flex-1 sm:flex-initial px-3.5 py-2 bg-white border border-rose-300 hover:bg-rose-50 text-rose-600 text-xs font-semibold rounded-xl transition active:scale-95"
                        >
                            Rechazar
                        </button>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button 
                            type="button"
                            @click="saveCustomPreferences()"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs transition active:scale-95"
                        >
                            Guardar Selección
                        </button>
                        <button 
                            type="button"
                            @click="modalOpen = false"
                            class="px-3.5 py-2 text-slate-500 hover:text-slate-800 text-xs font-medium transition"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- 3. Botón Flotante Permanente para Reabrir Políticas (Esquina inferior izquierda) -->
    <button 
        type="button"
        @click="openModal('cookies')"
        class="fixed bottom-4 left-4 z-40 p-2.5 bg-white/95 hover:bg-white text-slate-500 hover:text-brand-primary rounded-xl border border-slate-200/90 shadow-md backdrop-blur-md transition-all hover:scale-105 active:scale-95 flex items-center gap-2 group text-xs font-semibold"
        title="Consultar Políticas de Privacidad, Cookies y Tratamiento de Datos"
    >
        <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <span class="hidden group-hover:inline transition-all duration-200 text-slate-700">Políticas & Privacidad</span>
    </button>
</div>
