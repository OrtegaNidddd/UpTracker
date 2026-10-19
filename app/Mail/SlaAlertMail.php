<?php

namespace App\Mail;

use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlaAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array{
     *     status: string,
     *     uptime_sli: float,
     *     slo_availability_target: float,
     *     slo_latency_target_ms: int,
     *     avg_latency_ms: int,
     *     error_budget_percentage: float,
     *     message: string
     * } $slaData
     */
    public function __construct(
        public Service $service,
        public array $slaData
    ) {}

    public function envelope(): Envelope
    {
        $status = $this->slaData['status'];
        $prefix = $status === 'Incumple' ? '🚨 [SLA INCUMPLIDO]' : '⚠️ [ALERTA PREVENTIVA SLA]';

        return new Envelope(
            subject: "{$prefix} UpTracker: {$this->service->name} ({$status})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sla-alert',
            with: [
                'service' => $this->service,
                'slaData' => $this->slaData,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
