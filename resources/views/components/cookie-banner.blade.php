<div 
    x-data="{ 
        visible: false,
        init() {
            if (!localStorage.getItem('uptracker_cookie_consent')) {
                setTimeout(() => { this.visible = true; }, 800);
            }
        },
        accept() {
            localStorage.setItem('uptracker_cookie_consent', 'all');
            this.visible = false;
        },
        essentialOnly() {
            localStorage.setItem('uptracker_cookie_consent', 'essential');
            this.visible = false;
        }
    }"
    x-show="visible"
    x-cloak
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-8"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-8"
    class="fixed bottom-4 inset-x-4 sm:left-6 sm:bottom-6 sm:right-auto sm:max-w-md z-40"
>
    <div class="bg-white/95 backdrop-blur-lg border border-slate-200/80 rounded-2xl shadow-2xl p-5 text-brand-dark">
        <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 text-brand-primary flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-sm text-brand-dark flex items-center gap-1.5">
                    Privacidad y Cookies
                    <span class="text-[10px] uppercase font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Ley 1581 / 2012</span>
                </h4>
                <p class="mt-1 text-xs text-brand-muted leading-relaxed">
                    Utilizamos cookies esenciales y de análisis para garantizar la telemetría en tiempo real y la seguridad de tu sesión, conforme a la Ley 1581 de 2012 de Colombia.
                </p>
                <div class="mt-3.5 flex flex-wrap items-center gap-2">
                    <button 
                        @click="accept()"
                        class="px-3.5 py-1.5 bg-brand-primary hover:bg-blue-600 text-white text-xs font-semibold rounded-lg shadow-sm transition transform active:scale-95"
                    >
                        Aceptar Todo
                    </button>
                    <button 
                        @click="essentialOnly()"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition"
                    >
                        Solo Esenciales
                    </button>
                    <a 
                        href="#privacidad" 
                        class="text-[11px] text-brand-muted hover:text-brand-primary underline transition ml-auto"
                    >
                        Políticas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
