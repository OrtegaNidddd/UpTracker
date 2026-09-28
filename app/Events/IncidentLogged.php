<?php

namespace App\Events;

use App\Models\Incident;
use App\Models\Service;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentLogged
{
    use Dispatchable, SerializesModels;

    /**
     * Crea una nueva instancia del evento de incidente registrado.
     */
    public function __construct(
        public Incident $incident,
        public Service $service,
    ) {}
}
