<?php

namespace App\Notifications;

use App\Models\StripeAccount;
use App\Models\StripePayout;
use App\Notifications\Concerns\HasStripeAccountContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StripePayoutSuccessNotification extends Notification implements ShouldQueue
{
    use HasStripeAccountContext;
    use Queueable;

    public function __construct(
        public string $payoutId,
        public int $amount,
        public string $currency,
        public ?int $arrivalDate = null,
        public ?int $stripeAccountId = null,
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
        ).' '.strtoupper($this->currency);

        $account = $this->stripeAccountId
            ? StripeAccount::with('company')->find($this->stripeAccountId)
            : StripePayout::with('stripeAccount.company')->where('stripe_payout_id', $this->payoutId)->first()?->stripeAccount;

        return (new MailMessage)
            ->subject(
                'Stripe payout successful - '.$formattedAmount
                .' ['.$this->stripeAccountLabel($account).']'
            )
            ->cc([
                'kapils@allinit.com.au',
                'accounts@allinit.com.au',
            ])
            ->greeting('Hi '.($notifiable->name ?? 'there').',')
            ->view('emails.notifications.stripe-payout-success', [
                'payoutId' => $this->payoutId,
                'amount' => $formattedAmount,
                'currency' => strtoupper($this->currency),
                'arrivalDate' => $this->arrivalDate
                    ? date('d M Y', $this->arrivalDate)
                    : null,
                'notifiable' => $notifiable,
                'accountLabel' => $this->stripeAccountLabel($account),
                'companyName' => $this->stripeAccountCompanyName($account),
            ]);
    }
}
