<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StripePayoutSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $payoutId,
        public int $amount,
        public string $currency,
        public ?int $arrivalDate = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $formattedAmount = number_format(
            $this->amount / 100,
            2
        ) . ' ' . strtoupper($this->currency);

        return (new MailMessage)
            ->subject(
                'Stripe payout successful - ' . $formattedAmount
            )
            ->cc([
                'kapils@allinit.com.au',
                'accounts@allinit.com.au',
            ])
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->view('emails.notifications.stripe-payout-success', [
                'payoutId' => $this->payoutId,
                'amount' => $formattedAmount,
                'currency' => strtoupper($this->currency),
                'arrivalDate' => $this->arrivalDate
                    ? date('d M Y', $this->arrivalDate)
                    : null,
                'notifiable' => $notifiable,
            ]);
    }
}
