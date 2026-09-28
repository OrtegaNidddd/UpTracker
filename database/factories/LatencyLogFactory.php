<?php

namespace Database\Factories;

use App\Models\LatencyLog;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LatencyLog>
 */
class LatencyLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'latency_ms' => fake()->numberBetween(20, 250),
            'http_status_code' => 200,
            'status' => 'Up',
            'checked_at' => now(),
        ];
    }
}
