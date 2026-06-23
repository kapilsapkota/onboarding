<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSingleDirectDebit;
use App\Models\DirectDebitPayment;
use App\Services\XeroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class DirectDebitPaymentController extends Controller
{
    public function __construct(private readonly XeroService $xero) {}

    public function index(Request $request)
    {
        $query = DirectDebitPayment::with(['client', 'initiatedByUser'])
            ->latest('initiated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('initiated_by')) {
            $query->where('initiated_by_type', $request->initiated_by);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('initiated_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('initiated_at', '<=', $request->date_to);
        }

        if ($request->filled('xero_posted')) {
            match ($request->xero_posted) {
                'yes'    => $query->whereNotNull('xero_payment_id'),
                'no'     => $query->whereNull('xero_payment_id')->where('xero_post_attempted', false),
                'failed' => $query->where('xero_post_attempted', true)
                    ->whereNull('xero_payment_id')
                    ->whereNotNull('xero_post_error'),
                default  => null,
            };
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('xero_invoice_number', 'like', "%{$search}%")
                    ->orWhere('our_reference', 'like', "%{$search}%")
                    ->orWhere('gateway_payment_id', 'like', "%{$search}%")
                    ->orWhereHas('client', fn($q) => $q->where('company_name', 'like', "%{$search}%"));
            });
        }

        $payments = $query->paginate(25);

        return view('admin.direct-debit.index', compact('payments'));
    }

    public function show(DirectDebitPayment $directDebitPayment)
    {
        $payment = $directDebitPayment;
        $payment->load(['client', 'invoice', 'tenant.connection', 'initiatedByUser', 'retryOf', 'retries']);

        // ── Stripe ────────────────────────────────────────────────────────────
        $stripePaymentIntent = null;
        $stripeBalanceTx     = null;
        $stripeCharge        = null;
        $stripeError         = null;

        if ($payment->gateway === 'stripe' && $payment->gateway_payment_id) {
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));

                if (str_starts_with($payment->gateway_payment_id, 'pi_')) {
                    $stripePaymentIntent = $stripe->paymentIntents->retrieve(
                        $payment->gateway_payment_id,
                        ['expand' => ['latest_charge', 'latest_charge.balance_transaction', 'payment_method']]
                    );
                    $stripeCharge = $stripePaymentIntent->latest_charge ?? null;

                } elseif (str_starts_with($payment->gateway_payment_id, 'ch_')) {
                    $stripeCharge = $stripe->charges->retrieve(
                        $payment->gateway_payment_id,
                        ['expand' => ['balance_transaction', 'payment_method']]
                    );
                }

                if ($stripeCharge?->balance_transaction && is_object($stripeCharge->balance_transaction)) {
                    $stripeBalanceTx = $stripeCharge->balance_transaction;
                } elseif ($payment->stripe_balance_transaction_id) {
                    $stripeBalanceTx = $stripe->balanceTransactions->retrieve(
                        $payment->stripe_balance_transaction_id
                    );
                }

            } catch (\Stripe\Exception\AuthenticationException $e) {
                $stripeError = 'Stripe API key invalid.';
                Log::error('Stripe auth failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            } catch (\Stripe\Exception\InvalidRequestException $e) {
                $stripeError = 'Payment not found in Stripe: ' . $e->getMessage();
                Log::warning('Stripe invalid request', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            } catch (\Stripe\Exception\ApiConnectionException $e) {
                $stripeError = 'Could not connect to Stripe. Try again later.';
                Log::error('Stripe connection error', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            } catch (\Exception $e) {
                $stripeError = 'Stripe error: ' . $e->getMessage();
                Log::error('Stripe fetch failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            }
        }

        // ── Xero ──────────────────────────────────────────────────────────────
        $xeroPaymentData = null;
        $xeroInvoiceData = null;
        $xeroError       = null;

        if ($payment->xero_payment_id && $payment->tenant?->connection) {
            try {
                $xeroPaymentData = $this->xero->getPayment(
                    $payment->tenant,
                    $payment->xero_payment_id
                );

                if ($payment->xero_invoice_xero_id) {
                    $xeroInvoiceData = $this->xero->getInvoiceForTenant(
                        $payment->tenant,
                        $payment->xero_invoice_xero_id
                    );
                }

            } catch (\RuntimeException $e) {
                $xeroError = $e->getMessage();
                Log::warning('Xero fetch failed on show page', [
                    'payment_id' => $payment->id,
                    'error'      => $e->getMessage(),
                ]);

            } catch (\Exception $e) {
                $xeroError = 'Xero error: ' . $e->getMessage();
                Log::error('Xero unexpected error on show page', [
                    'payment_id' => $payment->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        } elseif ($payment->xero_payment_id && !$payment->tenant?->connection) {
            $xeroError = 'Xero tenant has no active connection.';
        }

        return view('admin.direct-debit.show', compact(
            'payment',
            'stripePaymentIntent',
            'stripeCharge',
            'stripeBalanceTx',
            'stripeError',
            'xeroPaymentData',
            'xeroInvoiceData',
            'xeroError',
        ));
    }

    // ── Cancel ────────────────────────────────────────────────────────────────
    public function cancel(Request $request, DirectDebitPayment $directDebitPayment): RedirectResponse
    {
        if ($directDebitPayment->status !== 'pending') {
            return back()->with('error', "Only pending payments can be cancelled (current status: {$directDebitPayment->status}).");
        }

        // If it was already submitted to Stripe, attempt to cancel the PaymentIntent
        if ($directDebitPayment->gateway === 'stripe' && $directDebitPayment->gateway_payment_id) {
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));

                if (str_starts_with($directDebitPayment->gateway_payment_id, 'pi_')) {
                    $intent = $stripe->paymentIntents->retrieve($directDebitPayment->gateway_payment_id);

                    // Can only cancel if not already succeeded/cancelled
                    if (in_array($intent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action', 'processing'])) {
                        $stripe->paymentIntents->cancel($directDebitPayment->gateway_payment_id);
                        Log::info('Stripe PaymentIntent cancelled', [
                            'payment_id' => $directDebitPayment->id,
                            'pi_id'      => $directDebitPayment->gateway_payment_id,
                        ]);
                    }
                }

            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // PI already in a terminal state — log but still mark ours as cancelled
                Log::warning('Could not cancel Stripe PI (already terminal)', [
                    'payment_id' => $directDebitPayment->id,
                    'error'      => $e->getMessage(),
                ]);

            } catch (\Exception $e) {
                Log::error('Stripe cancel failed', [
                    'payment_id' => $directDebitPayment->id,
                    'error'      => $e->getMessage(),
                ]);
                return back()->with('error', 'Could not cancel payment in Stripe: ' . $e->getMessage());
            }
        }

        $directDebitPayment->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Log::info('DirectDebitPayment cancelled', [
            'payment_id' => $directDebitPayment->id,
            'by_user'    => $request->user()->id,
        ]);

        return back()->with('success', "Payment {$directDebitPayment->our_reference} has been cancelled.");
    }

    // ── Retry ─────────────────────────────────────────────────────────────────
    public function retry(Request $request, DirectDebitPayment $directDebitPayment): RedirectResponse
    {
        if (! in_array($directDebitPayment->status, ['failed', 'cancelled'])) {
            return back()->with('error', "Only failed or cancelled payments can be retried (current status: {$directDebitPayment->status}).");
        }

        // Must have an invoice to retry against
        if (! $directDebitPayment->invoice) {
            return back()->with('error', 'Cannot retry — no invoice linked to this payment.');
        }

        // Don't retry if there's already an active payment for this invoice
        $existing = DirectDebitPayment::where('xero_invoice_id', $directDebitPayment->xero_invoice_id)
            ->whereIn('status', ['pending', 'processing', 'settled'])
            ->where('id', '!=', $directDebitPayment->id)
            ->first();

        if ($existing) {
            return back()->with('error', "Invoice already has an active payment (ID: {$existing->id}, status: {$existing->status}).");
        }

        $retry = DB::transaction(function () use ($directDebitPayment, $request) {
            return DirectDebitPayment::create([
                ...DirectDebitPayment::dataFromInvoice(
                    invoice:           $directDebitPayment->invoice,
                    initiatedByType:   'manual',
                    initiatedByUserId: $request->user()->id,
                    overrideAmount:    (float) $directDebitPayment->amount,
                    client:            $directDebitPayment->client,
                ),
                'retry_of_id'    => $directDebitPayment->id,
                'attempt_number' => $directDebitPayment->attempt_number + 1,
                'gateway'        => $directDebitPayment->gateway,
            ]);
        });

        ProcessSingleDirectDebit::dispatch($retry->id);

        Log::info('DirectDebitPayment retry created', [
            'original_id' => $directDebitPayment->id,
            'retry_id'    => $retry->id,
            'attempt'     => $retry->attempt_number,
            'by_user'     => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.directDebitPayment.show', $retry)
            ->with('success', "Retry created as attempt #{$retry->attempt_number} — processing now.");
    }

    // ── Post to Xero ──────────────────────────────────────────────────────────
    public function postToXero(Request $request, DirectDebitPayment $directDebitPayment): RedirectResponse
    {
        if ($directDebitPayment->status !== 'settled') {
            return back()->with('error', 'Only settled payments can be posted to Xero.');
        }

        if ($directDebitPayment->xero_payment_id) {
            return back()->with('error', 'This payment has already been posted to Xero.');
        }

        if (! $directDebitPayment->tenant?->connection) {
            return back()->with('error', 'No active Xero connection found for this tenant.');
        }

        if (! $directDebitPayment->xero_invoice_xero_id) {
            return back()->with('error', 'No Xero invoice ID on this payment — cannot post.');
        }

        if (! $directDebitPayment->xero_bank_account_id) {
            return back()->with('error', 'No bank account ID set on this payment — cannot post to Xero.');
        }

        try {
            $connection = $this->xero->refreshToken($directDebitPayment->tenant->connection);
            $tenantId   = $directDebitPayment->tenant->tenant_id;

            $payload = [
                'Invoice'   => ['InvoiceID' => $directDebitPayment->xero_invoice_xero_id],
                'Account'   => ['AccountID' => $directDebitPayment->xero_bank_account_id],
                'Date'      => ($directDebitPayment->settled_at ?? now())->format('Y-m-d'),
                'Amount'    => (float) $directDebitPayment->amount,
                'Reference' => $directDebitPayment->our_reference,
            ];

            $response = \Illuminate\Support\Facades\Http::withToken($connection->access_token)
                ->withHeaders(['Xero-tenant-id' => $tenantId])
                ->put('https://api.xero.com/api.xro/2.0/Payments', $payload);

            if ($response->failed()) {
                $errorBody = $response->body();

                $directDebitPayment->markXeroPostFailed($errorBody);

                Log::error('postToXero failed', [
                    'payment_id' => $directDebitPayment->id,
                    'status'     => $response->status(),
                    'body'       => $errorBody,
                ]);

                return back()->with('error', 'Xero rejected the payment: ' . $errorBody);
            }

            $xeroPaymentId = $response->json('Payments.0.PaymentID');

            if (! $xeroPaymentId) {
                $directDebitPayment->markXeroPostFailed('No PaymentID returned from Xero.');
                return back()->with('error', 'Xero did not return a PaymentID. Check the Xero audit log.');
            }

            $directDebitPayment->markXeroPosted($xeroPaymentId);

            Log::info('DirectDebitPayment posted to Xero', [
                'payment_id'      => $directDebitPayment->id,
                'xero_payment_id' => $xeroPaymentId,
                'by_user'         => $request->user()->id,
            ]);

            return back()->with('success', "Posted to Xero successfully. Xero Payment ID: {$xeroPaymentId}");

        } catch (\Exception $e) {
            $directDebitPayment->markXeroPostFailed($e->getMessage());

            Log::error('postToXero exception', [
                'payment_id' => $directDebitPayment->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to post to Xero: ' . $e->getMessage());
        }
    }
}
