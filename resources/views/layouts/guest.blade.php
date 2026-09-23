<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'UpTracker') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full font-sans antialiased text-brand-dark bg-gradient-to-br from-brand-ice via-white to-slate-100 selection:bg-brand-primary selection:text-white flex flex-col justify-between relative overflow-x-hidden">
        
        <!-- Ambient decorative background glows -->
        <div class="fixed -top-32 -left-32 w-80 h-80 bg-brand-primary/10 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="fixed -bottom-32 -right-32 w-96 h-96 bg-brand-primary/5 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] bg-gradient-to-tr from-brand-primary/5 to-transparent rounded-full blur-3xl pointer-events-none -z-10"></div>

        <!-- Header -->
        <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
            <a href="/" class="group flex items-center gap-3 transition-transform duration-200 hover:scale-[1.02]">
                <div class="w-10 h-10 rounded-xl bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary shadow-sm shadow-brand-primary/10 group-hover:bg-brand-primary/15 transition-all">
                    <x-application-logo class="w-6 h-6" />
                </div>
                <span class="text-xl font-bold tracking-tight text-brand-dark">UpTracker</span>
            </a>

            <a href="/" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-muted hover:text-brand-primary transition-colors py-2 px-3 rounded-lg hover:bg-white/80 border border-transparent hover:border-slate-200/80">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Volver al inicio</span>
            </a>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col justify-center items-center px-4 py-8 sm:px-6">
            <div class="w-full sm:max-w-md">
                <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl shadow-slate-200/70 border border-slate-200/80 p-7 sm:p-9 transition-all">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-5 text-center text-xs text-brand-muted">
            &copy; {{ date('Y') }} UpTracker &bull; Monitoreo de Disponibilidad y Servicios
        </footer>
    </body>
</html>
