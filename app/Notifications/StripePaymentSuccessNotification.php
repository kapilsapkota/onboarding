<?php

namespace App\Notifications;

use App\Models\StripeChargeBatchItem;
use App\Notifications\Concerns\HasStripeAccountContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StripePaymentSuccessNotification extends Notification implements ShouldQueue
{
    use HasStripeAccountContext;
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
            'batch.stripeAccount.company',
            'stripeAccount.company',
            'stripeCustomer',
            'stripePaymentMethod',
        ])->findOrFail($this->batchItemId);

        $account = $item->stripeAccount ?? $item->batch?->stripeAccount;

        return (new MailMessage)
            ->subject(
                'Stripe payment successful - '
                .($item->stripeCustomer?->name ?? 'Unknown customer')
                .' - '
                .$item->formattedAmount()
                .' ['.$this->stripeAccountLabel($account).']'
            )
            ->cc([
                'alit@allinit.com.au',
                'kapils@allinit.com.au',
                'accounts@allinit.com.au',
            ])
            ->greeting('Hi '.($notifiable->name ?: 'there').',')
            ->view('emails.notifications.stripe-payment-success', [
                'item' => $item,
                'notifiable' => $notifiable,
                'accountLabel' => $this->stripeAccountLabel($account),
                'companyName' => $this->stripeAccountCompanyName($account),
            ]);
    }
}
