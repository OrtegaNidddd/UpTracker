<?php

namespace App\Mail;

use App\Models\Incident;
use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public Service $service;

    /**
     * Crea una nueva instancia del mensaje de alerta.
     */
    public function __construct(
        public Incident $incident,
        ?Service $service = null,
    ) {
        $this->service = $service ?? $incident->service;
    }

    /**
     * Obtiene el sobre del mensaje.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[ALERTA UpTracker] {$this->service->name} ha registrado un incidente ({$this->incident->incident_type})",
        );
    }

    /**
     * Obtiene la definición de contenido del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.service-alert',
            with: [
                'incident' => $this->incident,
                'service' => $this->service,
            ],
        );
    }

    /**
     * Adjuntos para el mensaje.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
