<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-brand-dark leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                Canales de Alerta y Notificación
            </h2>

            <button 
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'new-channel-modal')"
                class="inline-flex items-center px-4 py-2 bg-brand-primary hover:opacity-90 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                + Configurar Canal
            </button>
        </div>
    </x-slot>

    <div class="py-8 bg-brand-ice min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Mensajes Flash de confirmación o error -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 text-sm font-medium rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-rose-50 border border-rose-300 text-rose-800 text-sm font-medium rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Explicación del alcance de notificaciones según directriz docente -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-blue-100 text-blue-800 uppercase">Alcance Real Implementado</span>
                    <h3 class="font-bold text-slate-800 text-base">Notificaciones Demostrables: Correo Electrónico, Discord y Telegram</h3>
                </div>
                <p class="text-sm text-slate-600 leading-relaxed">
                    UpTracker despacha alertas en tiempo real ante <strong>caídas de endpoints</strong>, <strong>degradación de latencia</strong> y <strong>alertas preventivas de riesgo/incumplimiento de SLA/SLO</strong>. A continuación puedes registrar tus destinos y ejecutar pruebas instantáneas con el botón <em>"Probar Conexión"</em>.
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
                                <th class="p-4">Destino / Webhook</th>
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
                                                ✉️ Correo Electrónico
                                            </span>
                                        @elseif($channel->type === 'Webhook_Discord')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                🎮 Discord Webhook
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                                ✈️ Telegram Webhook
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
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1 bg-brand-ice hover:bg-slate-200 text-brand-dark text-xs font-semibold rounded-md border border-slate-300 transition" title="Enviar notificación de prueba">
                                                ⚡ Probar Conexión
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
                                        No has configurado canales de notificación aún. Agrega un correo o webhook de Discord/Telegram para recibir alertas.
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
                Selecciona la vía de despacho e ingresa la dirección o URL receptora.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="type" value="Tipo de Canal" class="text-brand-slate" />
                    <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-brand-primary focus:ring-brand-primary rounded-md shadow-sm text-sm text-brand-dark" required>
                        <option value="Email" @selected(old('type') === 'Email')>✉️ Correo Electrónico (SMTP)</option>
                        <option value="Webhook_Discord" @selected(old('type') === 'Webhook_Discord')>🎮 Discord Webhook (URL)</option>
                        <option value="Webhook_Telegram" @selected(old('type') === 'Webhook_Telegram')>✈️ Telegram Webhook / Bot API (URL)</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="target_destination" value="Destino (Email o URL del Webhook)" class="text-brand-slate" />
                    <x-text-input 
                        id="target_destination" 
                        name="target_destination" 
                        type="text" 
                        class="mt-1 block w-full focus:border-brand-primary focus:ring-brand-primary font-mono text-xs" 
                        placeholder="ej: alertas@miempresa.com o https://discord.com/api/webhooks/..." 
                        :value="old('target_destination')" 
                        required 
                    />
                    <x-input-error :messages="$errors->get('target_destination')" class="mt-2" />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary" checked>
                    <label for="is_active" class="text-sm font-medium text-slate-700">Activar canal inmediatamente para alertas e incidentes</label>
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
