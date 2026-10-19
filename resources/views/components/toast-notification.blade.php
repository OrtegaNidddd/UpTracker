<div 
    x-data="{ 
        notifications: [],
        add(notice) {
            const id = Date.now() + Math.random();
            const item = {
                id,
                message: notice.message,
                type: notice.type || 'info', // 'success', 'error', 'warning', 'info'
                show: true
            };
            this.notifications.push(item);
            setTimeout(() => {
                this.remove(id);
            }, 4500);
        },
        remove(id) {
            const index = this.notifications.findIndex(n => n.id === id);
            if (index !== -1) {
                this.notifications[index].show = false;
                setTimeout(() => {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                }, 300);
            }
        }
    }"
    x-init="
        @if(session('success'))
            add({ message: @js(session('success')), type: 'success' });
        @endif
        @if(session('error'))
            add({ message: @js(session('error')), type: 'error' });
        @endif
        @if(session('warning'))
            add({ message: @js(session('warning')), type: 'warning' });
        @endif
        @if(session('status'))
            add({ message: @js(session('status')), type: 'info' });
        @endif
        window.addEventListener('notify', (e) => {
            if (e.detail) {
                add(typeof e.detail === 'string' ? { message: e.detail, type: 'info' } : e.detail);
            }
        });
    "
    class="fixed bottom-5 right-5 z-50 flex flex-col space-y-3 pointer-events-none max-w-sm w-full px-4"
>
    <template x-for="item in notifications" :key="item.id">
        <div 
            x-show="item.show"
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto rounded-xl p-4 shadow-xl border backdrop-blur-md flex items-start gap-3 text-sm transition-all"
            :class="{
                'bg-white/95 text-slate-800 border-emerald-500/30 shadow-emerald-500/10': item.type === 'success',
                'bg-white/95 text-slate-800 border-rose-500/30 shadow-rose-500/10': item.type === 'error',
                'bg-white/95 text-slate-800 border-amber-500/30 shadow-amber-500/10': item.type === 'warning',
                'bg-white/95 text-slate-800 border-blue-500/30 shadow-blue-500/10': item.type === 'info'
            }"
        >
            <!-- Iconos según tipo -->
            <div class="shrink-0 mt-0.5">
                <template x-if="item.type === 'success'">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </template>
                <template x-if="item.type === 'error'">
                    <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                </template>
                <template x-if="item.type === 'warning'">
                    <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                </template>
                <template x-if="item.type === 'info'">
                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </template>
            </div>

            <!-- Contenido -->
            <div class="flex-1 leading-snug font-medium text-slate-700" x-text="item.message"></div>

            <!-- Botón cerrar -->
            <button @click="remove(item.id)" class="text-slate-400 hover:text-slate-600 p-0.5 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
