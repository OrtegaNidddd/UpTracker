<x-guest-layout>
    <!-- Card Header -->
    <div class="mb-6">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand-primary/10 border border-brand-primary/20 text-brand-primary text-xs font-semibold mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-brand-primary animate-pulse"></span>
            Crear Cuenta
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight text-brand-dark">Empieza con UpTracker</h1>
        <p class="text-sm text-brand-muted mt-1">Supervisa la salud y disponibilidad de tus servicios.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ showPassword: false, showConfirmPassword: false }">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block font-medium text-xs uppercase tracking-wider text-slate-600 mb-1.5">
                {{ __('Nombre completo') }}
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <input id="name"
                       type="text"
                       name="name"
                       value="{{ old('name') }}"
                       required
                       autofocus
                       autocomplete="name"
                       placeholder="ej. Alex Morales"
                       class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50/60 hover:bg-white focus:bg-white border border-slate-200 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10 rounded-xl text-sm text-brand-dark placeholder-slate-400 transition-all shadow-sm" />
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block font-medium text-xs uppercase tracking-wider text-slate-600 mb-1.5">
                {{ __('Correo electrónico') }}
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autocomplete="username"
                       placeholder="tu@empresa.com"
                       class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50/60 hover:bg-white focus:bg-white border border-slate-200 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10 rounded-xl text-sm text-brand-dark placeholder-slate-400 transition-all shadow-sm" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block font-medium text-xs uppercase tracking-wider text-slate-600">
                    {{ __('Contraseña') }}
                </label>
                <span class="text-[11px] text-brand-muted">Mín. 8 caracteres</span>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input :type="showPassword ? 'text' : 'password'"
                       id="password"
                       name="password"
                       required
                       autocomplete="new-password"
                       placeholder="••••••••"
                       class="block w-full pl-10 pr-10 py-2.5 bg-slate-50/60 hover:bg-white focus:bg-white border border-slate-200 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10 rounded-xl text-sm text-brand-dark placeholder-slate-400 transition-all shadow-sm" />

                <!-- Toggle Password Visibility -->
                <button type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                        tabindex="-1"
                        :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'">
                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block font-medium text-xs uppercase tracking-wider text-slate-600 mb-1.5">
                {{ __('Confirmar contraseña') }}
            </label>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <input :type="showConfirmPassword ? 'text' : 'password'"
                       id="password_confirmation"
                       name="password_confirmation"
                       required
                       autocomplete="new-password"
                       placeholder="••••••••"
                       class="block w-full pl-10 pr-10 py-2.5 bg-slate-50/60 hover:bg-white focus:bg-white border border-slate-200 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10 rounded-xl text-sm text-brand-dark placeholder-slate-400 transition-all shadow-sm" />

                <!-- Toggle Confirm Password Visibility -->
                <button type="button"
                        @click="showConfirmPassword = !showConfirmPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                        tabindex="-1"
                        :title="showConfirmPassword ? 'Ocultar contraseña' : 'Ver contraseña'">
                    <svg x-show="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showConfirmPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <!-- Submit Button -->
        <div class="pt-3">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-brand-primary hover:bg-blue-600 active:bg-blue-700 text-white font-semibold text-sm rounded-xl shadow-md shadow-brand-primary/25 hover:shadow-lg hover:shadow-brand-primary/30 transition-all duration-200 transform active:scale-[0.99] focus:outline-none focus:ring-4 focus:ring-brand-primary/20">
                <span>{{ __('Crear mi Cuenta') }}</span>
                <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>

        <!-- Login Link -->
        <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-brand-muted">
            ¿Ya tienes una cuenta registrada?
            <a href="{{ route('login') }}" class="font-semibold text-brand-primary hover:underline ml-1">
                Inicia sesión aquí
            </a>
        </div>
    </form>
</x-guest-layout>
