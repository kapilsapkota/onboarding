<?php

namespace App\Notifications;

use App\Models\DirectDebitPayment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DirectDebitFailedAdminNotification extends Notification
{
    public function __construct(private DirectDebitPayment $ddPayment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->ddPayment->invoice;
        $client  = $invoice->client;

        return (new MailMessage)
            ->subject('Direct debit payment failed — ' . ($client->company_name ?? $client->name))
            ->error()
            ->greeting('Payment failed')
            ->line("A direct debit payment has failed and requires your attention.")
            ->line("**Client:** " . ($client->company_name ?? $client->name))
            ->line("**Invoice:** " . ($invoice->xero_invoice_number ?? $invoice->xero_invoice_id))
            ->line("**Amount:** " . number_format($this->ddPayment->amount / 100, 2) . ' ' . ($invoice->currency_code ?? 'AUD'))
            ->line("**Reason:** " . ($this->ddPayment->failure_reason ?? 'Unknown'))
            ->line("A replacement invoice has been created in Xero for re-collection.")
            ->action('View in dashboard', url('/admin/payments/' . $this->ddPayment->id));
    }
}
