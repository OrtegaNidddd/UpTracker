<?php

namespace App\Console\Commands;

use App\Models\LatencyLog;
use Illuminate\Console\Command;

class PruneLatencyLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:prune {--days=30 : Número de días de retención para los registros de latencia}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina registros históricos antiguos de latency_logs para optimizar el rendimiento y espacio en base de datos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days <= 0) {
            $this->components->error('El número de días de retención debe ser mayor a 0.');

            return self::FAILURE;
        }

        $cutoffDate = now()->subDays($days);

        $this->components->info("Iniciando purga de registros de latencia anteriores a {$cutoffDate->toDateTimeString()} ({$days} días)...");

        $deletedCount = LatencyLog::query()
            ->where('checked_at', '<', $cutoffDate)
            ->delete();

        $this->components->info("Purga completada exitosamente. Se eliminaron {$deletedCount} registros antiguos.");

        return self::SUCCESS;
    }
}
