<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Acceso Restringido | UpTracker</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full text-center space-y-6 bg-slate-800/80 border border-slate-700/60 rounded-2xl p-8 shadow-2xl backdrop-blur-sm">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-bold text-2xl">
            403
        </div>
        <div class="space-y-2">
            <h1 class="text-2xl font-bold tracking-tight text-white">Acceso Restringido</h1>
            <p class="text-sm text-slate-400 leading-relaxed">
                No tienes privilegios para interactuar con este recurso. Cada servicio, log de latencia e incidente está estrictamente aislado a su cuenta propietaria.
            </p>
        </div>
        <div class="pt-2">
            <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-semibold text-sm transition shadow-lg shadow-emerald-500/20">
                Regresar al inicio
            </a>
        </div>
        <p class="text-xs text-slate-500 font-mono">UpTracker Security Layer</p>
    </div>
</body>
</html>
