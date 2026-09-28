<?php

namespace Tests\Feature;

use App\Models\LatencyLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneLatencyLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_removes_logs_older_than_specified_days(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['user_id' => $user->id]);

        // Create log older than 30 days
        $oldLog = LatencyLog::factory()->create([
            'service_id' => $service->id,
            'checked_at' => now()->subDays(35),
        ]);

        // Create recent log
        $recentLog = LatencyLog::factory()->create([
            'service_id' => $service->id,
            'checked_at' => now()->subDays(5),
        ]);

        $this->artisan('monitor:prune --days=30')
            ->expectsOutputToContain('Purga completada exitosamente')
            ->assertSuccessful();

        $this->assertDatabaseMissing('latency_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('latency_logs', ['id' => $recentLog->id]);
    }

    public function test_prune_command_fails_with_invalid_days(): void
    {
        $this->artisan('monitor:prune --days=0')
            ->expectsOutputToContain('El número de días de retención debe ser mayor a 0.')
            ->assertFailed();
    }
}
