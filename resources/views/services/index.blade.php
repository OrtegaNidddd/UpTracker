<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-extrabold text-2xl text-brand-dark leading-tight flex items-center gap-2.5">
                    <svg class="w-6 h-6 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                    Gestión de Servicios y Endpoints
                </h2>
                <p class="text-xs text-brand-muted mt-1">Configura y administra las URLs supervisadas, métodos HTTP, intervalos y umbrales de latencia.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold rounded-lg shadow-xs transition">
                    <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10" />
                    </svg>
                    Ver Telemetría
                </a>

                <!-- Botón modal con Alpine.js -->
                <button 
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'new-service-modal')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-primary hover:bg-blue-600 text-white text-xs font-semibold rounded-lg shadow-md shadow-brand-primary/20 transition transform active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Endpoint
                </button>
            </div>
        </div>
    </x-slot>

    <div 
        class="py-8 bg-slate-50 min-h-screen"
        x-data="{
            editingService: {
                id: null,
                name: '',
                url: '',
                check_interval: 60,
                http_method: 'GET',
                latency_threshold_ms: 1000
            },
            openEdit(service) {
                this.editingService = {
                    id: service.id,
                    name: service.name,
                    url: service.url,
                    check_interval: service.interval_seconds || service.check_interval || 60,
                    http_method: service.http_method || 'GET',
                    latency_threshold_ms: service.latency_threshold_ms || 1000
                };
                $dispatch('open-modal', 'edit-service-modal');
            }
        }"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 1. Tarjetas de Resumen General -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Monitoreados</span>
                    <p class="text-3xl font-black text-brand-dark mt-2">{{ $totalServices }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Operativos (Online)</span>
                    <p class="text-3xl font-black text-emerald-600 mt-2">{{ $onlineServices }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Caídos o Degradados</span>
                    <p class="text-3xl font-black text-rose-600 mt-2">{{ $offlineServices }}</p>
                </div>
            </div>

            <!-- 2. Tabla / Lista de Servicios Monitoreados -->
            <div class="bg-white shadow-xs rounded-2xl border border-slate-200/80 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-lg text-brand-dark">Endpoints Web Supervisados</h3>
                        <p class="text-xs text-brand-muted">Gestión integral de sondeos periódicos y umbrales de alerta</p>
                    </div>
                    <span class="text-xs text-slate-500 bg-slate-50 border border-slate-200 px-3 py-1 rounded-full font-medium">
                        {{ $services->count() }} endpoints activos
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs font-semibold">
                            <tr>
                                <th class="p-4">Estado</th>
                                <th class="p-4">Nombre</th>
                                <th class="p-4">URL / Endpoint</th>
                                <th class="p-4">Método & Frecuencia</th>
                                <th class="p-4">Última Revisión</th>
                                <th class="p-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($services as $service)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="p-4">
                                        @if($service->status === 'online')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Online
                                            </span>
                                        @elseif($service->status === 'degraded')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Lento
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Offline
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <a href="{{ route('services.show', $service) }}" class="font-bold text-brand-dark hover:text-brand-primary transition">
                                            {{ $service->name }}
                                        </a>
                                        @if($service->latency_threshold_ms)
                                            <span class="block text-[11px] text-slate-400">Umbral: {{ $service->latency_threshold_ms }}ms</span>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono text-xs text-brand-muted truncate max-w-xs">{{ $service->url }}</span>
                                            <a href="{{ $service->url }}" target="_blank" rel="noopener noreferrer" class="text-slate-400 hover:text-brand-primary">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-block px-1.5 py-0.5 rounded font-mono text-[11px] bg-slate-100 text-slate-700 font-semibold">{{ $service->http_method ?? 'GET' }}</span>
                                        <span class="text-xs text-slate-500 font-medium ml-1.5">{{ $service->formatted_interval }}</span>
                                    </td>
                                    <td class="p-4 text-slate-400 text-xs">
                                        {{ $service->last_checked_at ? $service->last_checked_at->diffForHumans() : 'Pendiente' }}
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Ver Detalles -->
                                            <a href="{{ route('services.show', $service) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-brand-primary text-xs font-semibold rounded-lg transition" title="Ver métricas avanzadas y bitácora">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Detalles
                                            </a>

                                            <!-- Editar Modal trigger -->
                                            <button 
                                                type="button"
                                                @click="openEdit({{ json_encode($service) }})"
                                                class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition"
                                                title="Editar configuración"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>

                                            <!-- Eliminar -->
                                            <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('¿Seguro que deseas eliminar este servicio?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Eliminar servicio">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-12 text-center">
                                        <div class="max-w-sm mx-auto space-y-3">
                                            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-brand-primary mx-auto flex items-center justify-center">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                                            </div>
                                            <h4 class="font-bold text-base text-brand-dark">No hay servicios monitoreados</h4>
                                            <p class="text-xs text-brand-muted">Comienza agregando tu primera API, sitio web o microservicio para activar el sondeo de salud y latencia en vivo.</p>
                                            <button 
                                                x-on:click.prevent="$dispatch('open-modal', 'new-service-modal')"
                                                class="mt-2 inline-flex items-center gap-1.5 px-4 py-2 bg-brand-primary text-white font-semibold text-xs rounded-xl shadow-md hover:bg-blue-600 transition"
                                            >
                                                + Registrar Primer Endpoint
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Modal Editar Servicio (Alpine prellenado) -->
        <x-modal name="edit-service-modal" focusable>
            <form 
                method="POST" 
                x-bind:action="'/services/' + editingService.id" 
                class="p-6 text-xs"
                x-data="{ saving: false }"
                @submit="saving = true"
            >
                @csrf
                @method('PUT')
                <h2 class="text-lg font-bold text-brand-dark">
                    Editar Endpoint
                </h2>
                <p class="mt-1 text-xs text-brand-muted">
                    Modifica los parámetros de supervisión del servicio seleccionado.
                </p>

                <div class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="edit_modal_name" value="Nombre del Servicio" class="font-semibold text-slate-700" />
                        <input id="edit_modal_name" name="name" type="text" x-model="editingService.name" class="mt-1 block w-full rounded-lg border-slate-300 text-xs focus:border-brand-primary focus:ring-brand-primary" required />
                    </div>

                    <div>
                        <x-input-label for="edit_modal_url" value="URL del Endpoint" class="font-semibold text-slate-700" />
                        <input id="edit_modal_url" name="url" type="url" x-model="editingService.url" class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-xs focus:border-brand-primary focus:ring-brand-primary" required />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="edit_modal_http_method" value="Método HTTP" class="font-semibold text-slate-700" />
                            <select id="edit_modal_http_method" name="http_method" x-model="editingService.http_method" class="mt-1 block w-full rounded-lg border-slate-300 text-xs focus:border-brand-primary focus:ring-brand-primary">
                                <option value="GET">GET (Petición completa)</option>
                                <option value="HEAD">HEAD (Solo cabeceras)</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="edit_modal_check_interval" value="Intervalo de Chequeo" class="font-semibold text-slate-700" />
                            <select id="edit_modal_check_interval" name="check_interval" x-model="editingService.check_interval" class="mt-1 block w-full rounded-lg border-slate-300 text-xs focus:border-brand-primary focus:ring-brand-primary">
                                <option value="30">Cada 30 segundos</option>
                                <option value="60">Cada 1 minuto</option>
                                <option value="300">Cada 5 minutos</option>
                                <option value="600">Cada 10 minutos</option>
                                <option value="900">Cada 15 minutos</option>
                                <option value="1800">Cada 30 minutos</option>
                                <option value="3600">Cada 1 hora</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="edit_modal_threshold" value="Umbral de Alerta de Latencia (ms)" class="font-semibold text-slate-700" />
                        <input id="edit_modal_threshold" name="latency_threshold_ms" type="number" min="50" max="60000" x-model="editingService.latency_threshold_ms" class="mt-1 block w-full rounded-lg border-slate-300 text-xs focus:border-brand-primary focus:ring-brand-primary" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        Cancelar
                    </x-secondary-button>

                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-brand-primary hover:bg-blue-600 text-white font-semibold text-xs rounded-lg shadow-sm transition disabled:opacity-50" x-bind:disabled="saving">
                        <span x-text="saving ? 'Guardando...' : 'Guardar Cambios'"></span>
                    </button>
                </div>
            </form>
        </x-modal>
    </div>

    <!-- 3. Modal de Registro de Servicio (RF-01) -->
    <x-modal name="new-service-modal" :show="$errors->isNotEmpty()" focusable>
        <form 
            method="POST" 
            action="{{ route('services.store') }}" 
            class="p-6 text-xs"
            x-data="{ saving: false }"
            @submit="saving = true"
        >
            @csrf
            <h2 class="text-lg font-bold text-brand-dark">
                Registrar Nuevo Endpoint
            </h2>
            <p class="mt-1 text-xs text-brand-muted">
                Ingresa los datos del microservicio o API que quieres supervisar en tiempo real.
            </p>

            <div class="mt-5 space-y-4">
                <div>
                    <x-input-label for="name" value="Nombre del Servicio" class="font-semibold text-slate-700" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full text-xs" placeholder="Ej: API Backend Pagos" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="url" value="URL del Endpoint (HTTP/HTTPS)" class="font-semibold text-slate-700" />
                    <x-text-input id="url" name="url" type="url" class="mt-1 block w-full font-mono text-xs" placeholder="https://api.ejemplo.com/health" :value="old('url')" required />
                    <x-input-error :messages="$errors->get('url')" class="mt-1.5" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="http_method" value="Método HTTP" class="font-semibold text-slate-700" />
                        <select id="http_method" name="http_method" class="mt-1 block w-full border-slate-300 rounded-lg text-xs focus:border-brand-primary focus:ring-brand-primary" required>
                            <option value="GET" @selected(old('http_method', 'GET') === 'GET')>GET (Monitoreo con payload)</option>
                            <option value="HEAD" @selected(old('http_method') === 'HEAD')>HEAD (Rápido, solo headers)</option>
                        </select>
                        <x-input-error :messages="$errors->get('http_method')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="check_interval" value="Intervalo de Chequeo" class="font-semibold text-slate-700" />
                        <select id="check_interval" name="check_interval" class="mt-1 block w-full border-slate-300 rounded-lg text-xs focus:border-brand-primary focus:ring-brand-primary" required>
                            <option value="30" @selected(old('check_interval') == 30)>Cada 30 segundos</option>
                            <option value="60" @selected(old('check_interval', 60) == 60)>Cada 1 minuto (Recomendado)</option>
                            <option value="300" @selected(old('check_interval') == 300)>Cada 5 minutos</option>
                            <option value="600" @selected(old('check_interval') == 600)>Cada 10 minutos</option>
                            <option value="900" @selected(old('check_interval') == 900)>Cada 15 minutos</option>
                            <option value="1800" @selected(old('check_interval') == 1800)>Cada 30 minutos</option>
                            <option value="3600" @selected(old('check_interval') == 3600)>Cada 1 hora</option>
                        </select>
                        <x-input-error :messages="$errors->get('check_interval')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <x-input-label for="latency_threshold_ms" value="Umbral de Alerta de Latencia (ms)" class="font-semibold text-slate-700" />
                    <x-text-input id="latency_threshold_ms" name="latency_threshold_ms" type="number" min="50" max="60000" class="mt-1 block w-full text-xs" placeholder="1000" :value="old('latency_threshold_ms', 1000)" />
                    <p class="text-[11px] text-slate-400 mt-1">Si la respuesta tarda más de este umbral se creará una degradación de servicio.</p>
                    <x-input-error :messages="$errors->get('latency_threshold_ms')" class="mt-1.5" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-brand-primary hover:bg-blue-600 text-white font-semibold text-xs rounded-lg shadow-sm transition disabled:opacity-50" x-bind:disabled="saving">
                    <span x-text="saving ? 'Guardando...' : 'Guardar y Monitorear'"></span>
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>