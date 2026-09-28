<x-guest-layout>
    <!-- Card Header -->
    <div class="mb-6">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand-primary/10 border border-brand-primary/20 text-brand-primary text-xs font-semibold mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-brand-primary animate-pulse"></span>
            Acceso a la plataforma
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight text-brand-dark">Iniciar Sesión</h1>
        <p class="text-sm text-brand-muted mt-1">Ingresa tus credenciales para supervisar tus servicios.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

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
                       autofocus
                       autocomplete="username"
                       placeholder="tu@empresa.com"
                       class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50/60 hover:bg-white focus:bg-white border border-slate-200 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10 rounded-xl text-sm text-brand-dark placeholder-slate-400 transition-all shadow-sm" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div x-data="{ showPassword: false }">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block font-medium text-xs uppercase tracking-wider text-slate-600">
                    {{ __('Contraseña') }}
                </label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-brand-primary hover:underline transition-colors" href="{{ route('password.request') }}">
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </a>
                @endif
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
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="block w-full pl-10 pr-10 py-2.5 bg-slate-50/60 hover:bg-white focus:bg-white border border-slate-200 focus:border-brand-primary focus:ring-4 focus:ring-brand-primary/10 rounded-xl text-sm text-brand-dark placeholder-slate-400 transition-all shadow-sm" />

                <!-- Toggle Password Visibility Button -->
                <button type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                        tabindex="-1"
                        :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'">
                    <!-- Eye icon -->
                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <!-- Eye slash icon -->
                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <div class="pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer select-none">
                <input id="remember_me"
                       type="checkbox"
                       class="w-4 h-4 rounded border-slate-300 text-brand-primary shadow-sm focus:ring-brand-primary/20 focus:ring-2 cursor-pointer transition"
                       name="remember">
                <span class="ms-2.5 text-xs font-medium text-slate-600">{{ __('Mantener sesión iniciada') }}</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-brand-primary hover:bg-blue-600 active:bg-blue-700 text-white font-semibold text-sm rounded-xl shadow-md shadow-brand-primary/25 hover:shadow-lg hover:shadow-brand-primary/30 transition-all duration-200 transform active:scale-[0.99] focus:outline-none focus:ring-4 focus:ring-brand-primary/20">
                <span>{{ __('Iniciar Sesión') }}</span>
                <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>

        <!-- Register Link -->
        @if (Route::has('register'))
            <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-brand-muted">
                ¿Aún no tienes una cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-brand-primary hover:underline ml-1">
                    Regístrate aquí
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>
