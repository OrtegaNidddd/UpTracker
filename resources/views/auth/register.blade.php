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

    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ submitting: false, showPassword: false, showConfirmPassword: false }" @submit="submitting = true">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block font-medium text-xs uppercase tracking-wider text-slate-600 mb-1.5">
                {{ __('Nombre completo') }}
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <x-reicon name="user" class="w-4 h-4" />
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
                    <x-reicon name="envelope" class="w-4 h-4" />
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
                    <x-reicon name="lock-password" class="w-4 h-4" />
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
                    <x-reicon name="eye" x-show="!showPassword" class="w-4 h-4" />
                    <x-reicon name="eye-closed" x-show="showPassword" x-cloak class="w-4 h-4" />
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
                    <x-reicon name="shield-check" class="w-4 h-4" />
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
                    <x-reicon name="eye" x-show="!showConfirmPassword" class="w-4 h-4" />
                    <x-reicon name="eye-closed" x-show="showConfirmPassword" x-cloak class="w-4 h-4" />
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <!-- Ley 1581 de 2012 & Términos de Servicio -->
        <div class="pt-2">
            <label class="flex items-start gap-2.5 cursor-pointer text-xs text-brand-muted select-none">
                <input type="checkbox" 
                       name="terms_and_privacy" 
                       id="terms_and_privacy" 
                       required 
                       class="mt-0.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20 transition cursor-pointer">
                <span class="leading-relaxed">
                    Acepto el <strong class="text-brand-dark">Tratamiento de Datos Personales</strong> conforme a la <span class="font-semibold text-brand-primary">Ley 1581 de 2012 de Colombia</span> y los <a href="#terminos" class="underline text-brand-primary hover:text-blue-600">Términos de Servicio</a>.
                </span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit"
                    x-bind:disabled="submitting"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-brand-primary hover:bg-blue-600 active:bg-blue-700 disabled:opacity-70 disabled:cursor-not-allowed text-white font-semibold text-sm rounded-xl shadow-md shadow-brand-primary/25 hover:shadow-lg hover:shadow-brand-primary/30 transition-all duration-200 transform active:scale-[0.99] focus:outline-none focus:ring-4 focus:ring-brand-primary/20">
                <template x-if="submitting">
                    <x-reicon name="refresh" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" />
                </template>
                <span x-text="submitting ? 'Creando cuenta...' : '{{ __('Crear mi Cuenta') }}'"></span>
                <x-reicon name="arrow-right" x-show="!submitting" class="w-4 h-4 transition-transform group-hover:translate-x-0.5" />
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
