<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
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
            'incident_type' => 'Down',
            'started_at' => now(),
            'resolved_at' => null,
            'duration_seconds' => null,
            'details' => 'Endpoint no responde.',
        ];
    }
}
