<?php

namespace App\Mail\Quotes;

use App\Models\QuoteDelivery;
use App\Services\Quotes\QuotePdfService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for quote delivery emails.
 *
 * Attaches the already-generated PDF from local storage.
 * Does NOT regenerate the PDF — it reads the file that
 * QuotePdfService::generate() already stored on disk.
 *
 * The public quote URL is included in the email body when available.
 * If it is null (public URL step failed), the email is still sent
 * without the link — better than not sending at all.
 */
class QuoteDeliveryMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly QuoteDelivery  $delivery,
        private readonly QuotePdfService $pdfService,
    ) {}

    // -------------------------------------------------------------------------
    // Envelope
    // -------------------------------------------------------------------------

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->delivery->email_subject
            ?? $this->delivery->quote->email_subject,
        );
    }

    // -------------------------------------------------------------------------
    // Content
    // -------------------------------------------------------------------------

    public function content(): Content
    {
        $quote = $this->delivery->quote;

        return new Content(
            markdown: 'emails.quotes.delivery',
            with: [
                'quote'          => $quote,
                'delivery'       => $this->delivery,
                'clientName'     => $quote->contact_name ?? $quote->client_name ?? 'there',
                'quoteNumber'    => $quote->quote_number,
                'quoteTotal'     => $quote->total,
                'quoteExpiry'    => $quote->expires_at,
                'publicUrl'      => $this->delivery->public_url,
                'extraMessage'   => $this->delivery->email_message,
            ],
        );
    }

    // -------------------------------------------------------------------------
    // Attachments
    // -------------------------------------------------------------------------

    public function attachments(): array
    {
        if (! $this->pdfService->pdfExists($this->delivery)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk(
                disk: $this->delivery->pdf_disk ?? 'local',
                path: $this->delivery->pdf_path,
            )->as($this->delivery->pdf_filename)
                ->withMime('application/pdf'),
        ];
    }
}
