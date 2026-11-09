<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-brand-dark leading-tight flex items-center gap-2">
                <x-reicon name="bell" class="w-6 h-6 text-brand-primary" />
                Canales de Aviso Oportuno (Correo Electrónico y WhatsApp)
            </h2>

            <button 
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'new-channel-modal')"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-primary hover:opacity-90 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                <x-reicon name="add-circle" class="w-4 h-4" />
                <span>Configurar Canal</span>
            </button>
        </div>
    </x-slot>

    <div class="py-8 bg-brand-ice min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Mensajes Flash de confirmación o error -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 text-sm font-medium rounded-xl flex items-center gap-3">
                    <x-reicon name="check-circle" class="w-5 h-5 text-emerald-600 shrink-0" />
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-rose-50 border border-rose-300 text-rose-800 text-sm font-medium rounded-xl flex items-center gap-3">
                    <x-reicon name="triangle-warning" class="w-5 h-5 text-rose-600 shrink-0" />
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Explicación del alcance de notificaciones según directriz docente -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-blue-100 text-blue-800 uppercase">Canales Oficiales de Aviso</span>
                    <h3 class="font-bold text-slate-800 text-base">Notificaciones Exclusivas por Correo Electrónico y WhatsApp</h3>
                </div>
                <p class="text-sm text-slate-600 leading-relaxed">
                    UpTracker envía avisos claros y fáciles de entender exclusivamente a través de <strong>Correo Electrónico (Email)</strong> y la <strong>API de WhatsApp (WhatsApp Cloud API)</strong> cuando una página web se cae, responde muy lento o entra en estado <strong>En riesgo</strong>. La velocidad de entrega depende de la conexión a internet.
                </p>
            </div>

            <!-- Listado de Canales -->
            <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-lg text-brand-dark">Canales Registrados</h3>
                    <span class="text-xs text-brand-muted">Total: {{ $channels->count() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-brand-muted uppercase text-xs">
                            <tr>
                                <th class="p-4">Tipo</th>
                                <th class="p-4">Correo o Número de WhatsApp</th>
                                <th class="p-4">Estado</th>
                                <th class="p-4">Creado</th>
                                <th class="p-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($channels as $channel)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-4">
                                        @if($channel->type === 'Email')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                <x-reicon name="envelope" class="w-3.5 h-3.5" />
                                                <span>Correo Electrónico</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <x-reicon name="whatsapp" class="w-3.5 h-3.5 text-emerald-600" />
                                                <span>WhatsApp (API Oficial)</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 font-mono text-xs text-brand-dark truncate max-w-sm">
                                        {{ $channel->target_destination }}
                                    </td>
                                    <td class="p-4">
                                        @if($channel->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-600">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-slate-400 text-xs">
                                        {{ $channel->created_at->diffForHumans() }}
                                    </td>
                                    <td class="p-4 text-right space-x-2">
                                        <!-- Botón de Prueba en vivo -->
                                        <form method="POST" action="{{ route('notification-channels.test', $channel) }}" class="inline-block">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 bg-brand-ice hover:bg-slate-200 text-brand-dark text-xs font-semibold rounded-md border border-slate-300 transition" title="Enviar notificación de prueba">
                                                <x-reicon name="bolt" class="w-3.5 h-3.5 text-amber-500" />
                                                <span>Probar Conexión</span>
                                            </button>
                                        </form>

                                        <!-- Alternar estado -->
                                        <form method="POST" action="{{ route('notification-channels.update', $channel) }}" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs text-slate-600 hover:text-brand-primary underline">
                                                {{ $channel->is_active ? 'Pausar' : 'Activar' }}
                                            </button>
                                        </form>

                                        <!-- Eliminar -->
                                        <form method="POST" action="{{ route('notification-channels.destroy', $channel) }}" class="inline-block" onsubmit="return confirm('¿Deseas eliminar este canal?')">
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
                                    <td colspan="5" class="p-8 text-center text-brand-muted">
                                        No has configurado canales de aviso aún. Agrega tu Correo Electrónico o número de WhatsApp para recibir alertas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal para Agregar Nuevo Canal -->
    <x-modal name="new-channel-modal" :show="$errors->isNotEmpty()" focusable>
        <form method="POST" action="{{ route('notification-channels.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-bold text-brand-dark">
                Configurar Nuevo Canal de Alerta
            </h2>
            <p class="mt-1 text-sm text-brand-muted">
                Selecciona si deseas recibir el aviso por Correo Electrónico o por WhatsApp.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="type" value="Medio de Aviso" class="text-brand-slate" />
                    <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-brand-primary focus:ring-brand-primary rounded-md shadow-sm text-sm text-brand-dark" required>
                        <option value="Email" @selected(old('type') === 'Email')>✉️ Correo Electrónico (Email)</option>
                        <option value="WhatsApp" @selected(old('type') === 'WhatsApp')>💬 WhatsApp (WhatsApp Cloud API)</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="target_destination" value="Destino (Correo Electrónico o Número de WhatsApp)" class="text-brand-slate" />
                    <x-text-input 
                        id="target_destination" 
                        name="target_destination" 
                        type="text" 
                        class="mt-1 block w-full focus:border-brand-primary focus:ring-brand-primary font-mono text-xs" 
                        placeholder="ej: alertas@minegocio.com o +573001234567" 
                        :value="old('target_destination')" 
                        required 
                    />
                    <x-input-error :messages="$errors->get('target_destination')" class="mt-2" />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary" checked>
                    <label for="is_active" class="text-sm font-medium text-slate-700">Activar canal inmediatamente para recibir avisos</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-primary-button class="bg-brand-primary hover:opacity-90">
                    Guardar Canal
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
