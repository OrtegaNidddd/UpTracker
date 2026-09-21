<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'UpTracker') }} - Monitoreo de Servicios</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gradient-to-br from-brand-ice via-white to-slate-100 min-h-screen font-sans text-brand-dark flex flex-col justify-between selection:bg-brand-primary selection:text-white">
        
        <!-- Header minimalista (solo identidad de marca) -->
        <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12v6m0-6l-2 2m2-2l2 2" />
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-brand-dark">UpTracker</span>
            </div>

            @auth
                <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-primary hover:underline">
                    Ir al Dashboard &rarr;
                </a>
            @endauth
        </header>

        <!-- Tarjeta Central de Acceso -->
        <main class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-200/80 p-8 sm:p-10 text-center">
                
                <!-- Badge Estado -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold mb-6">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Monitoreo en Tiempo Real
                </div>

                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight leading-tight mb-3">
                    Bienvenido a UpTracker
                </h1>
                
                <p class="text-brand-muted text-sm leading-relaxed mb-8">
                    Supervisa la disponibilidad, latencia y salud de tus endpoints y microservicios desde un solo lugar.
                </p>

                @if (Route::has('login'))
                    <div class="space-y-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="w-full inline-flex items-center justify-center px-5 py-3.5 bg-brand-primary hover:opacity-90 text-white font-semibold rounded-xl shadow-md shadow-brand-primary/20 transition">
                                Entrar a mi Dashboard &rarr;
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center px-5 py-3.5 bg-brand-primary hover:opacity-90 text-white font-semibold rounded-xl shadow-md shadow-brand-primary/25 transition">
                                Iniciar Sesión
                            </a>

                            @if (Route::has('register'))
                                <div class="pt-2 text-sm text-brand-muted">
                                    ¿Aún no tienes una cuenta?
                                    <a href="{{ route('register') }}" class="font-semibold text-brand-primary hover:underline ml-1">
                                        Regístrate
                                    </a>
                                </div>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-6 text-center text-xs text-brand-muted">
            &copy; {{ date('Y') }} UpTracker. Todos los derechos reservados.
        </footer>

    </body>
</html>
