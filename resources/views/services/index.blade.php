<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-brand-dark leading-tight flex items-center gap-2">
                <!-- Icono Nube UpTracker -->
                <svg class="w-6 h-6 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12v6m0-6l-2 2m2-2l2 2" />
                </svg>
                UpTracker Dashboard
            </h2>

            <!-- Botón modal con Alpine.js -->
            <button 
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'new-service-modal')"
                class="inline-flex items-center px-4 py-2 bg-brand-primary hover:opacity-90 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                + Nuevo Servicio
            </button>
        </div>
    </x-slot>

    <div class="py-8 bg-brand-ice min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Mensajes Flash de sesión -->
            @if(session('success'))
                <div class="p-4 bg-brand-success/15 border border-brand-success text-brand-dark text-sm font-medium rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <!-- 1. Tarjetas de Resumen General -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-muted">Total Monitoreados</span>
                    <p class="text-3xl font-extrabold text-brand-dark mt-2">{{ $totalServices }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-mint">En Línea (Online)</span>
                    <p class="text-3xl font-extrabold text-brand-mint mt-2">{{ $onlineServices }}</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold uppercase tracking-wider text-rose-500">Caídos (Offline)</span>
                    <p class="text-3xl font-extrabold text-rose-600 mt-2">{{ $offlineServices }}</p>
                </div>
            </div>

            <!-- 2. Tabla / Lista de Servicios Monitoreados -->
            <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-lg text-brand-dark">Servicios Web Activos</h3>
                    <span class="text-xs text-brand-muted">Frecuencia de monitoreo</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-brand-muted uppercase text-xs">
                            <tr>
                                <th class="p-4">Estado</th>
                                <th class="p-4">Nombre</th>
                                <th class="p-4">URL / Endpoint</th>
                                <th class="p-4">Intervalo</th>
                                <th class="p-4">Última Revisión</th>
                                <th class="p-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($services as $service)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-4">
                                        @if($service->status === 'online')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-mint/15 text-brand-mint">
                                                <span class="w-2 h-2 rounded-full bg-brand-mint"></span> Online
                                            </span>
                                        @elseif($service->status === 'degraded')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Lento
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Offline
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 font-semibold text-brand-dark">{{ $service->name }}</td>
                                    <td class="p-4 font-mono text-xs text-brand-muted truncate max-w-xs">{{ $service->url }}</td>
                                    <td class="p-4 text-brand-slate font-medium">{{ $service->formatted_interval }}</td>
                                    <td class="p-4 text-slate-400 text-xs">
                                        {{ $service->last_checked_at ? $service->last_checked_at->diffForHumans() : 'Pendiente' }}
                                    </td>
                                    <td class="p-4 text-right">
                                        <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('¿Deseas eliminar este servicio?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-semibold">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-brand-muted">
                                        No tienes endpoints registrados todavía. Dale a "+ Nuevo Servicio" para comenzar a monitorear.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- 3. Modal de Registro de Servicio (RF-01) -->
    <x-modal name="new-service-modal" :show="$errors->isNotEmpty()" focusable>
        <form method="POST" action="{{ route('services.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-bold text-brand-dark">
                Registrar Nuevo Endpoint
            </h2>
            <p class="mt-1 text-sm text-brand-muted">
                Ingresa los datos del microservicio o API que quieres supervisar.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="name" value="Nombre del Servicio" class="text-brand-slate" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full focus:border-brand-primary focus:ring-brand-primary" placeholder="Ej: API Backend Pagos" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="url" value="URL del Endpoint (HTTP/HTTPS)" class="text-brand-slate" />
                    <x-text-input id="url" name="url" type="url" class="mt-1 block w-full focus:border-brand-primary focus:ring-brand-primary" placeholder="https://api.ejemplo.com/health" :value="old('url')" required />
                    <x-input-error :messages="$errors->get('url')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="check_interval" value="Intervalo de Chequeo" class="text-brand-slate" />
                    <select id="check_interval" name="check_interval" class="mt-1 block w-full border-gray-300 focus:border-brand-primary focus:ring-brand-primary rounded-md shadow-sm text-sm text-brand-dark" required>
                        <option value="30" @selected(old('check_interval') == 30)>Cada 30 segundos</option>
                        <option value="60" @selected(old('check_interval', 60) == 60)>Cada 1 minuto (Recomendado)</option>
                        <option value="300" @selected(old('check_interval') == 300)>Cada 5 minutos</option>
                        <option value="600" @selected(old('check_interval') == 600)>Cada 10 minutos</option>
                        <option value="900" @selected(old('check_interval') == 900)>Cada 15 minutos</option>
                        <option value="1800" @selected(old('check_interval') == 1800)>Cada 30 minutos</option>
                        <option value="3600" @selected(old('check_interval') == 3600)>Cada 1 hora</option>
                    </select>
                    <x-input-error :messages="$errors->get('check_interval')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-primary-button class="bg-brand-primary hover:opacity-90">
                    Guardar y Monitorear
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>