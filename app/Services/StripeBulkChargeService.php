<?php

namespace App\Services;

use App\Jobs\ProcessStripeCharge;
use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class StripeBulkChargeService
{
    /**
     * Creates the batch, batch items, and dispatches queue jobs.
     *
     * @param  array<int, array{stripe_customer_id: int, stripe_payment_method_id: int, amount: int}>  $items
     */
    public function createBatch(array $items, ?int $createdBy = null, ?int $stripeAccountId = null): StripeChargeBatch
    {
        $this->validateItems($items, $stripeAccountId);

        return DB::transaction(function () use ($items, $createdBy, $stripeAccountId) {
            $totalAmount = collect($items)->sum('amount');

            $batch = StripeChargeBatch::create([
                'reference' => $this->generateReference(),
                'stripe_account_id' => $stripeAccountId,
                'customer_count' => count($items),
                'total_amount' => $totalAmount,
                'currency' => 'aud',
                'status' => 'pending',
                'created_by' => $createdBy,
            ]);

            foreach ($items as $item) {
                $batchItem = StripeChargeBatchItem::create([
                    'batch_id' => $batch->id,
                    'stripe_account_id' => $stripeAccountId,
                    'stripe_customer_id' => $item['stripe_customer_id'],
                    'stripe_payment_method_id' => $item['stripe_payment_method_id'],
                    'amount' => $item['amount'],
                    'currency' => 'aud',
                    'description' => $item['description'] ?? null,
                    'status' => 'pending',
                ]);

                ProcessStripeCharge::dispatch($batchItem);
            }

            $batch->update(['started_at' => now(), 'status' => 'processing']);

            return $batch;
        });
    }

    /** Validates each item references a real, eligible customer/payment method pair. */
    private function validateItems(array $items, ?int $stripeAccountId): void
    {
        foreach ($items as $item) {
            $customer = StripeCustomer::find($item['stripe_customer_id']);

            if (! $customer) {
                $this->fail("Customer ID {$item['stripe_customer_id']} not found.");
            }

            $this->assertCustomerBelongs($customer, $item['stripe_customer_id'], $stripeAccountId);

            $pm = StripePaymentMethod::where('id', $item['stripe_payment_method_id'])
                ->where('stripe_customer_id', $customer->id)
                ->where('type', 'au_becs_debit')
                ->where('status', 'active')
                ->first();

            $this->assertPaymentMethodEligible($pm, $item['stripe_payment_method_id'], $stripeAccountId);

            if (! isset($item['amount']) || $item['amount'] < 1) {
                $this->fail("Amount must be at least 1 cent for customer {$customer->stripe_customer_id}.");
            }
        }
    }

    private function assertCustomerBelongs(StripeCustomer $customer, int|string $customerInputId, ?int $stripeAccountId): void
    {
        if ($stripeAccountId !== null && $customer->stripe_account_id !== $stripeAccountId) {
            $this->fail("Customer ID {$customerInputId} does not belong to this Stripe account.");
        }
    }

    private function assertPaymentMethodEligible(?StripePaymentMethod $pm, int|string $paymentMethodId, ?int $stripeAccountId): void
    {
        if (! $pm) {
            $this->fail("Payment method ID {$paymentMethodId} is not eligible.");
        }

        if ($stripeAccountId !== null && $pm->stripe_account_id !== null && $pm->stripe_account_id !== $stripeAccountId) {
            $this->fail("Payment method ID {$paymentMethodId} does not belong to this Stripe account.");
        }
    }

    private function fail(string $message): never
    {
        throw new InvalidArgumentException($message);
    }

    /** Generates a unique batch reference. */
    private function generateReference(): string
    {
        do {
            $ref = 'BATCH-'.strtoupper(Str::random(8));
        } while (StripeChargeBatch::where('reference', $ref)->exists());

        return $ref;
    }
}
