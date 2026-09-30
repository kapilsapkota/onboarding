<?php

namespace App\Notifications;

use App\Models\DirectDebitPayment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DirectDebitFailedCustomerNotification extends Notification
{
    public function __construct(private DirectDebitPayment $ddPayment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->ddPayment->loadMissing(['company', 'invoice.client.company']);

        $invoice = $this->ddPayment->invoice;
        $merchant = $this->ddPayment->company?->name
            ?? $invoice?->client?->company?->name;

        return (new MailMessage)
            ->subject('Payment unsuccessful - action required')
            ->error()
            ->greeting('Your payment was unsuccessful')
            ->line('We were unable to collect a direct debit payment from your account.')
            ->line('**Merchant:** '.($merchant ?? config('app.name')))
            ->line('**Invoice:** '.($invoice?->xero_invoice_number ?? $invoice?->xero_invoice_id ?? 'Unknown'))
            ->line('**Amount:** '.number_format($this->ddPayment->amount / 100, 2).' '.($invoice?->currency_code ?? 'AUD'))
            ->line('**Reason:** '.($this->ddPayment->failure_reason ?? 'Payment declined'))
            ->line('A new invoice has been issued. Please contact us to arrange payment or update your payment details.')
            ->action('Contact us', url('/contact'));
    }
}
