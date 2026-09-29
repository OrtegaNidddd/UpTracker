<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatusWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_public_status_page(): void
    {
        Service::factory()->create([
            'name' => 'API Pública Gateway',
            'is_active' => true,
        ]);

        $response = $this->get('/status/public');

        $response->assertStatus(200);
        $response->assertSee('API Pública Gateway');
        $response->assertSee('Diagnóstico Global');
        $response->assertSee('Página de Estado Oficial');
    }

    public function test_public_status_page_shows_empty_state_when_no_services(): void
    {
        $response = $this->get('/status/public');

        $response->assertStatus(200);
        $response->assertSee('Sin Servicios Monitoreados');
    }
}
