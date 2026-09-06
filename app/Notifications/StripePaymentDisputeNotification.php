<?php

namespace App\Notifications;

use App\Models\StripeChargeBatchItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StripePaymentDisputeNotification extends Notification implements ShouldQueue
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
            'stripeCustomer',
            'stripePaymentMethod',
        ])->findOrFail($this->batchItemId);

        $stripeData = $item->stripe_data ?? [];

        $dispute = $stripeData['dispute'] ?? [];

        $customerName = $item->stripeCustomer?->name ?? 'Unknown customer';

        $reason = $dispute['reason'] ?? 'general';

        $disputeStatus = $dispute['status'] ?? 'needs_response';

        $disputeAmount = isset($dispute['amount'])
            ? '$' . number_format($dispute['amount'] / 100, 2)
            : $item->formattedAmount();

        $createdAt = isset($dispute['created'])
            ? date('d/m/Y H:i:s', $dispute['created'])
            : now()->format('d/m/Y H:i:s');

        $humanReadableReason = $this->humanReadableReason($reason);

        $humanReadableStatus = $this->humanReadableStatus($disputeStatus);

        return (new MailMessage)
            ->subject(
                'Stripe payment disputed - '
                . $customerName
                . ' - '
                . $disputeAmount
            )
            ->cc([
                'alit@allinit.com.au',
                'kapils@allinit.com.au',
            ])
            ->view('emails.notifications.stripe-payment-dispute', [
                'item' => $item,
                'stripeData' => $stripeData,
                'dispute' => $dispute,
                'customerName' => $customerName,
                'humanReadableReason' => $humanReadableReason,
                'humanReadableStatus' => $humanReadableStatus,
                'disputeAmount' => $disputeAmount,
                'createdAt' => $createdAt,
                'notifiable' => $notifiable,
            ]);
    }

    private function humanReadableReason(string $reason): string
    {
        return match ($reason) {
            'bank_cannot_process' => 'The customer’s bank could not process the payment.',
            'check' => 'The payment was disputed because the customer believes it was made in error.',
            'credit_not_processed' => 'The customer claims they were due a credit or refund that was not received.',
            'customer_initiated' => 'The customer initiated a dispute with their bank.',
            'debit_not_recognised' => 'The customer does not recognise this direct debit payment.',
            'duplicate' => 'The customer claims they were charged more than once.',
            'fraudulent' => 'The payment was reported as fraudulent.',
            'general' => 'The payment has been disputed by the customer or their bank.',
            'incorrect_account_details' => 'The payment was disputed because of incorrect account details.',
            'insufficient_funds' => 'The payment was disputed due to insufficient funds.',
            'product_not_received' => 'The customer claims the product or service was not received.',
            'product_unacceptable' => 'The customer claims the product or service was unacceptable.',
            'subscription_canceled' => 'The customer claims the subscription was cancelled but the payment was still taken.',
            'unrecognized' => 'The customer does not recognise this payment.',
            default => ucwords(str_replace('_', ' ', $reason)),
        };
    }

    private function humanReadableStatus(string $status): string
    {
        return match ($status) {
            'needs_response',
            'warning_needs_response' => 'Response required',

            'under_review' => 'Under review',

            'won' => 'Won',

            'lost' => 'Lost',

            'closed' => 'Closed',

            default => ucwords(str_replace('_', ' ', $status)),
        };
    }
}
