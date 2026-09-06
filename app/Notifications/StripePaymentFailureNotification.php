<?php

namespace App\Notifications;

use App\Models\StripeChargeBatchItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StripePaymentFailureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $batchItemId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
{
    $item = StripeChargeBatchItem::with([
        'batch',
        'batch.createdBy',
        'stripeCustomer',
        'stripePaymentMethod',
    ])->findOrFail($this->batchItemId);

    $stripeData = $item->stripe_data ?? [];

    return (new MailMessage)
        ->cc([
            'alit@allinit.com.au',
            'accounts@allinit.com.au',
            'ea@allinit.com.au',
        ])
        ->subject(
            'Stripe payment failed - '
            . ($item->stripeCustomer?->name ?? 'Unknown customer')
            . ' - '
            . $item->formattedAmount()
        )
        ->view('emails.notifications.stripe-payment-failed', [
            'item' => $item,
            'stripeData' => $stripeData,
            'notifiable' => $notifiable,
        ]);
}
}
