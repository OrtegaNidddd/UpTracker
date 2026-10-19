<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-extrabold text-2xl text-brand-dark leading-tight flex items-center gap-2.5">
                    <svg class="w-6 h-6 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Canales de Alerta y Notificación
                </h2>
                <p class="text-xs text-brand-muted mt-1">Configura dónde recibir alertas automáticas cuando un endpoint caiga o experimente degradaciones.</p>
            </div>

            <div class="flex items-center gap-2">
                <button 
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'new-channel-modal')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-primary hover:bg-blue-600 text-white text-xs font-semibold rounded-lg shadow-md shadow-brand-primary/20 transition transform active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
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
                $discordCount = $channels->where('type', 'Webhook_Discord')->count();
                $telegramCount = $channels->where('type', 'Webhook_Telegram')->count();
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
                            <span>Discord: {{ $discordCount }}</span>
                            <span>•</span>
                            <span>Telegram: {{ $telegramCount }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Tarjetas Informativas de Integraciones Soportadas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200/70 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-brand-primary flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-brand-dark">Correo Electrónico</h4>
                        <p class="text-[11px] text-brand-muted">Notificaciones detalladas con causa e incidente.</p>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200/70 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.893.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-brand-dark">Discord Webhook</h4>
                        <p class="text-[11px] text-brand-muted">Mensajes automáticos en tus canales de Discord.</p>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200/70 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-500 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-brand-dark">Telegram Bot / Webhook</h4>
                        <p class="text-[11px] text-brand-muted">Alertas directas a grupos de ingeniería o chats privados.</p>
                    </div>
                </div>
            </div>

            <!-- 3. Listado de Canales -->
            <div class="bg-white shadow-xs rounded-2xl border border-slate-200/80 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-brand-dark">Destinos de Alerta Registrados</h3>
                        <p class="text-xs text-brand-muted">Administra los canales donde UpTracker emitirá notificaciones</p>
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
                                <th class="p-4">Destino / URL</th>
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
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                Correo Electrónico
                                            </span>
                                        @elseif($channel->type === 'Webhook_Discord')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.893.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                                                Discord Webhook
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                                                Telegram Bot
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
                                            <!-- Editar Modal trigger -->
                                            <button 
                                                type="button"
                                                @click="openEdit({{ json_encode($channel) }})"
                                                class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition"
                                                title="Editar destino"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>

                                            <!-- Eliminar -->
                                            <form method="POST" action="{{ route('notification-channels.destroy', $channel) }}" onsubmit="return confirm('¿Deseas desvincular este canal de alertas?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Eliminar canal">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-12 text-center">
                                        <div class="max-w-sm mx-auto space-y-3">
                                            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-brand-primary mx-auto flex items-center justify-center">
                                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                            </div>
                                            <h4 class="font-bold text-base text-brand-dark">No tienes canales de alerta configurados</h4>
                                            <p class="text-xs text-brand-muted">Registra tu correo, canal de Discord o bot de Telegram para ser notificado de inmediato cuando un endpoint sufra una caída.</p>
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
                            <option value="Email">Correo Electrónico</option>
                            <option value="Webhook_Discord">Discord Webhook</option>
                            <option value="Webhook_Telegram">Telegram Bot / Webhook</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="edit_target_destination" value="Destino / URL" class="font-semibold text-slate-700" />
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
                    if (this.type === 'Email') return 'alertas-ops@miempresa.com';
                    if (this.type === 'Webhook_Discord') return 'https://discord.com/api/webhooks/123456789/abcdef...';
                    return 'https://api.telegram.org/bot<TOKEN>/sendMessage?chat_id=<CHAT_ID>';
                },
                get helperText() {
                    if (this.type === 'Email') return 'Enviaremos alertas por correo de alta prioridad.';
                    if (this.type === 'Webhook_Discord') return 'Crea una integración Webhook en tu servidor de Discord y pega aquí la URL generada.';
                    return 'Pega la URL de webhook de tu Bot de Telegram o endpoint intermedio de despacho.';
                }
            }"
            @submit="saving = true"
        >
            @csrf
            <h2 class="text-lg font-bold text-brand-dark">
                Registrar Nuevo Destino de Alerta
            </h2>
            <p class="mt-1 text-xs text-brand-muted">
                Selecciona la plataforma e ingresa la dirección de entrega de incidencias.
            </p>

            <div class="mt-5 space-y-4">
                <div>
                    <x-input-label for="new_type" value="Plataforma de Notificación" class="font-semibold text-slate-700" />
                    <select id="new_type" name="type" x-model="type" class="mt-1 block w-full rounded-lg border-slate-300 text-xs focus:border-brand-primary focus:ring-brand-primary" required>
                        <option value="Email">Correo Electrónico (Email)</option>
                        <option value="Webhook_Discord">Discord (Webhook de Servidor)</option>
                        <option value="Webhook_Telegram">Telegram (Bot Webhook)</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="new_target" value="Destino / URL Webhook" class="font-semibold text-slate-700" />
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
