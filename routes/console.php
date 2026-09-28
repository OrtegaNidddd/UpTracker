<?php

use App\Jobs\CheckEndpointStatus;
use App\Models\Service;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Programación de Sondeo de Endpoints en UpTracker
|--------------------------------------------------------------------------
| Itera sobre los servicios activos y registra el despacho asíncrono
| del Job CheckEndpointStatus según su interval_seconds configurado.
| Laravel 11/13 admite programación nativa sub-minuto (ej. everyThirtySeconds).
*/
try {
    if (Schema::hasTable('services')) {
        Service::query()
            ->where('is_active', true)
            ->each(function (Service $service) {
                $scheduledEvent = Schedule::job(new CheckEndpointStatus($service))
                    ->withoutOverlapping(10);

                match (true) {
                    $service->interval_seconds <= 10 => $scheduledEvent->everyTenSeconds(),
                    $service->interval_seconds <= 15 => $scheduledEvent->everyFifteenSeconds(),
                    $service->interval_seconds <= 20 => $scheduledEvent->everyTwentySeconds(),
                    $service->interval_seconds <= 30 => $scheduledEvent->everyThirtySeconds(),
                    $service->interval_seconds <= 60 => $scheduledEvent->everyMinute(),
                    $service->interval_seconds <= 120 => $scheduledEvent->everyTwoMinutes(),
                    $service->interval_seconds <= 300 => $scheduledEvent->everyFiveMinutes(),
                    $service->interval_seconds <= 600 => $scheduledEvent->everyTenMinutes(),
                    $service->interval_seconds <= 900 => $scheduledEvent->everyFifteenMinutes(),
                    $service->interval_seconds <= 1800 => $scheduledEvent->everyThirtyMinutes(),
                    default => $scheduledEvent->hourly(),
                };
            });
    }
} catch (Throwable) {
    // Resguardo en caso de que la base de datos no esté accesible o migrada al arrancar
}

/*
|--------------------------------------------------------------------------
| Tarea Diaria de Purga de Logs Históricos
|--------------------------------------------------------------------------
| Elimina logs de latencia con más de 30 días de antigüedad para mantener
| un rendimiento óptimo de la base de datos y cumplir el RNF de bajo consumo.
*/
Schedule::command('monitor:prune --days=30')->daily();
