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
 * Customer-facing confirmation of their Direct Debit Request.
 */
class DirectDebitMandateConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public ?Company $company = null,
        public ?string $submittedVia = null,
    ) {
        $this->client->loadMissing(['contacts', 'company']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Direct Debit Authority Confirmation — '.($this->company?->name ?? config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.clients.ddr-mandate-confirmation',
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
