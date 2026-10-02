<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a direct-debit authority is submitted (per-account DDR links
 * and the legacy direct-debit form) — separate from the full onboarding
 * notification so the team can tell them apart at a glance.
 */
class DirectDebitConfigured extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public ?Company $company = null,
        public ?string $submittedVia = null,
    ) {
        $this->client->loadMissing('contacts');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Direct Debit Configured Successfully for '.($this->client->company_name ?? 'Unknown Company'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.clients.ddr-configured',
            with: [
                'logoUrl' => $this->company?->logo
                    ? asset('images/'.$this->company->logo)
                    : asset('images/allinit.png'),
                'companyName' => $this->company?->name ?? config('app.name'),
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
