<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewClientCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client->load('contacts');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Client Onboarded: ' . ($this->client->company_name ?? 'Unknown Company'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.clients.created',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->client->contacts_file_path) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->client->contacts_file_path)
                ->as('staff-contacts.csv');
        }

        return $attachments;
    }
}
