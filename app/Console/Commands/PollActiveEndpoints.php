<?php

namespace App\Console\Commands;

use App\Jobs\CheckEndpointStatus;
use App\Models\Service;
use Illuminate\Console\Command;

class PollActiveEndpoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:poll {--force : Forzar sondeo de todos los servicios activos sin evaluar el intervalo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha el job de sondeo CheckEndpointStatus para todos los servicios activos correspondientes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $services = Service::query()->where('is_active', true)->get();

        if ($services->isEmpty()) {
            $this->components->info('No hay servicios activos registrados para monitorear.');

            return self::SUCCESS;
        }

        $dispatchedCount = 0;

        foreach ($services as $service) {
            $shouldDispatch = $force;

            if (! $shouldDispatch) {
                $lastCheckedAt = $service->last_checked_at;
                $shouldDispatch = $lastCheckedAt === null || $lastCheckedAt->diffInSeconds(now()) >= $service->interval_seconds;
            }

            if ($shouldDispatch) {
                CheckEndpointStatus::dispatch($service);
                $dispatchedCount++;
            }
        }

        $this->components->info("Se despacharon {$dispatchedCount} tareas de verificación a la cola.");

        return self::SUCCESS;
    }
}
