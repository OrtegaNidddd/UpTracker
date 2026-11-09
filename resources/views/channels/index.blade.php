<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-extrabold text-2xl text-brand-dark leading-tight flex items-center gap-2.5">
                    <x-reicon name="bell" class="w-6 h-6 text-brand-primary" />
                    Canales de Alerta y Notificación (Email y WhatsApp)
                </h2>
                <p class="text-xs text-brand-muted mt-1">Configura tu Correo Electrónico o WhatsApp para recibir avisos oportunos cuando una página web se caiga o esté lenta.</p>
            </div>

            <div class="flex items-center gap-2">
                <button 
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'new-channel-modal')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-primary hover:bg-blue-600 text-white text-xs font-semibold rounded-lg shadow-md shadow-brand-primary/20 transition transform active:scale-95">
                    <x-reicon name="add-circle" class="w-4 h-4" />
                    Nuevo Canal de Alerta
                </button>
            </div>
        </div>
    </x-slot>

    <div 
        class="py-8 bg-slate-50 min-h-screen"
        x-data="{
            editingChannel: {
                id: null,
                type: 'Email',
                target_destination: '',
                is_active: true
            },
            openEdit(channel) {
                this.editingChannel = {
                    id: channel.id,
                    type: channel.type,
                    target_destination: channel.target_destination,
                    is_active: channel.is_active
                };
                $dispatch('open-modal', 'edit-channel-modal');
            }
        }"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 1. Tarjetas de Resumen General -->
            @php
                $totalChannels = $channels->count();
                $activeChannels = $channels->where('is_active', true)->count();
                $emailCount = $channels->where('type', 'Email')->count();
                $whatsappCount = $channels->where('type', 'WhatsApp')->count();
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Canales Configurados</span>
                    <p class="text-3xl font-black text-brand-dark mt-2">{{ $totalChannels }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Canales Activos</span>
                    <p class="text-3xl font-black text-emerald-600 mt-2">{{ $activeChannels }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Destinos Activos</span>
                        <div class="flex items-center gap-3 mt-2 text-xs font-semibold text-slate-700">
                            <span>Email: {{ $emailCount }}</span>
                            <span>•</span>
                            <span>WhatsApp: {{ $whatsappCount }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Tarjetas Informativas de Integraciones Soportadas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200/70 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-brand-primary flex items-center justify-center shrink-0">
                        <x-reicon name="envelope" class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-brand-dark">Correo Electrónico (Email)</h4>
                        <p class="text-[11px] text-brand-muted">Reportes claros y detallados en tu bandeja de entrada.</p>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200/70 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <x-reicon name="whatsapp" class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-brand-dark">WhatsApp (API Oficial)</h4>
                        <p class="text-[11px] text-brand-muted">Mensajes instantáneos al teléfono móvil mediante WhatsApp Cloud API.</p>
                    </div>
                </div>
            </div>

            <!-- 3. Listado de Canales -->
            <div class="bg-white shadow-xs rounded-2xl border border-slate-200/80 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-brand-dark">Destinos de Alerta Registrados</h3>
                        <p class="text-xs text-brand-muted">Administra los canales donde UpTracker enviará avisos oportunos</p>
                    </div>
                    <span class="text-xs text-slate-500 bg-slate-50 border border-slate-200 px-3 py-1 rounded-full font-medium">
                        {{ $totalChannels }} canales
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs font-semibold">
                            <tr>
                                <th class="p-4">Tipo de Canal</th>
                                <th class="p-4">Correo o WhatsApp</th>
                                <th class="p-4">Estado</th>
                                <th class="p-4">Registrado</th>
                                <th class="p-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($channels as $channel)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="p-4">
                                        @if($channel->type === 'Email')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-brand-primary border border-blue-200">
                                                <x-reicon name="envelope" class="w-3.5 h-3.5" />
                                                <span>Correo Electrónico</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <x-reicon name="whatsapp" class="w-3.5 h-3.5 text-emerald-600" />
                                                <span>WhatsApp</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 font-mono text-xs text-slate-700 truncate max-w-sm">
                                        {{ $channel->target_destination }}
                                    </td>
                                    <td class="p-4">
                                        <form method="POST" action="{{ route('notification-channels.toggle', $channel) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button 
                                                type="submit" 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold transition {{ $channel->is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}"
                                                title="Haz clic para activar o pausar"
                                            >
                                                <span class="w-2 h-2 rounded-full {{ $channel->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                {{ $channel->is_active ? 'Activo' : 'Pausado' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="p-4 text-xs text-slate-400">
                                        {{ $channel->created_at ? $channel->created_at->diffForHumans() : '---' }}
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button 
                                                type="button"
                                                @click="openEdit({{ json_encode($channel) }})"
                                                class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition"
                                                title="Editar destino"
                                            >
                                                <x-reicon name="pen-square" class="w-4 h-4" />
                                            </button>

                                            <form method="POST" action="{{ route('notification-channels.destroy', $channel) }}" onsubmit="return confirm('¿Deseas desvincular este canal de alertas?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Eliminar canal">
                                                    <x-reicon name="trash" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-12 text-center">
                                        <div class="max-w-sm mx-auto space-y-3">
                                            <h4 class="font-bold text-base text-brand-dark">No tienes canales de alerta configurados</h4>
                                            <p class="text-xs text-brand-muted">Registra tu Correo Electrónico o número de WhatsApp para recibir avisos inmediatos cuando una página sufra una caída.</p>
                                            <button 
                                                x-on:click.prevent="$dispatch('open-modal', 'new-channel-modal')"
                                                class="mt-2 inline-flex items-center gap-1.5 px-4 py-2 bg-brand-primary text-white font-semibold text-xs rounded-xl shadow-md hover:bg-blue-600 transition"
                                            >
                                                + Registrar Primer Canal
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

        <!-- Modal Editar Canal (Alpine) -->
        <x-modal name="edit-channel-modal" focusable>
            <form 
                method="POST" 
                x-bind:action="'/notification-channels/' + editingChannel.id" 
                class="p-6 text-xs"
                x-data="{ saving: false }"
                @submit="saving = true"
            >
                @csrf
                @method('PUT')
                <h2 class="text-lg font-bold text-brand-dark">
                    Editar Canal de Alerta
                </h2>
                <p class="mt-1 text-xs text-brand-muted">
                    Modifica el destino o tipo de alerta configurado.
                </p>

                <div class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="edit_channel_type" value="Tipo de Canal" class="font-semibold text-slate-700" />
                        <select id="edit_channel_type" name="type" x-model="editingChannel.type" class="mt-1 block w-full rounded-lg border-slate-300 text-xs focus:border-brand-primary focus:ring-brand-primary">
                            <option value="Email">Correo Electrónico (Email)</option>
                            <option value="WhatsApp">WhatsApp (API Oficial)</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="edit_target_destination" value="Correo o Número de WhatsApp" class="font-semibold text-slate-700" />
                        <input id="edit_target_destination" name="target_destination" type="text" x-model="editingChannel.target_destination" class="mt-1 block w-full rounded-lg border-slate-300 text-xs focus:border-brand-primary focus:ring-brand-primary" required />
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="editingChannel.is_active" class="rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
                            <span class="text-xs text-slate-700 font-medium">Canal activado para recibir alertas</span>
                        </label>
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

    <!-- Modal Registrar Nuevo Canal -->
    <x-modal name="new-channel-modal" :show="$errors->isNotEmpty()" focusable>
        <form 
            method="POST" 
            action="{{ route('notification-channels.store') }}" 
            class="p-6 text-xs"
            x-data="{ 
                type: 'Email',
                saving: false,
                get placeholderText() {
                    if (this.type === 'Email') return 'alertas@minegocio.com';
                    return '+573001234567';
                },
                get helperText() {
                    if (this.type === 'Email') return 'Enviaremos avisos claros a tu correo electrónico.';
                    return 'Ingresa tu número en formato internacional (+57...) o el endpoint de WhatsApp Cloud API.';
                }
            }"
            @submit="saving = true"
        >
            @csrf
            <h2 class="text-lg font-bold text-brand-dark">
                Registrar Nuevo Destino de Alerta
            </h2>
            <p class="mt-1 text-xs text-brand-muted">
                Selecciona Correo Electrónico o WhatsApp e ingresa tu dirección de contacto.
            </p>

            <div class="mt-5 space-y-4">
                <div>
                    <x-input-label for="new_type" value="Plataforma de Notificación" class="font-semibold text-slate-700" />
                    <select id="new_type" name="type" x-model="type" class="mt-1 block w-full rounded-lg border-slate-300 text-xs focus:border-brand-primary focus:ring-brand-primary" required>
                        <option value="Email">Correo Electrónico (Email)</option>
                        <option value="WhatsApp">WhatsApp (WhatsApp Cloud API)</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="new_target" value="Correo o Número de WhatsApp" class="font-semibold text-slate-700" />
                    <input id="new_target" name="target_destination" type="text" :placeholder="placeholderText" class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-xs focus:border-brand-primary focus:ring-brand-primary" required />
                    <p class="text-[11px] text-slate-400 mt-1" x-text="helperText"></p>
                    <x-input-error :messages="$errors->get('target_destination')" class="mt-1.5" />
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
                        <span class="text-xs text-slate-700 font-medium">Activar este canal inmediatamente</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-brand-primary hover:bg-blue-600 text-white font-semibold text-xs rounded-lg shadow-sm transition disabled:opacity-50" x-bind:disabled="saving">
                    <span x-text="saving ? 'Guardando...' : 'Registrar Canal'"></span>
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
