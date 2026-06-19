<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSingleDirectDebit;
use App\Models\Client;
use App\Models\ClientCharge;
use App\Models\DirectDebitPayment;
use App\Models\XeroInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class AdminChargeController extends Controller
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function charge(Request $request, Client $client)
    {
        $data = $request->validate([
            'amount'      => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        if (!$client->stripe_customer_id) {
            return back()->with('error', 'This client has no Stripe customer ID.');
        }

        try {
            // 1. Get the customer's saved au_becs_debit payment method
            $paymentMethods = $this->stripe->paymentMethods->all([
                'customer' => $client->stripe_customer_id,
                'type'     => 'au_becs_debit',
            ]);

            if (empty($paymentMethods->data)) {
                return back()->with('error', 'No BECS Direct Debit payment method found for this client.');
            }

            $paymentMethodId = $paymentMethods->data[0]->id;

            // 2. Create & confirm the PaymentIntent in one call
            $intent = $this->stripe->paymentIntents->create([
                'amount'               => (int) round($data['amount'] * 100),
                'currency'             => 'aud',
                'customer'             => $client->stripe_customer_id,
                'payment_method'       => $paymentMethodId,
                'payment_method_types' => ['au_becs_debit'],
                'description'          => $data['description'],
                'confirm'              => true, // confirms immediately using saved mandate
            ]);

            // 3. Save to DB
            ClientCharge::create([
                'client_id'         => $client->id,
                'payment_intent_id' => $intent->id,
                'amount'            => (int) round($data['amount'] * 100),
                'currency'          => 'aud',
                'status'            => $intent->status,
                'description'       => $data['description'],
            ]);

            return back()->with('success',
                "Charge initiated: \${$data['amount']} AUD — PaymentIntent {$intent->id} ({$intent->status}). BECS settles in 3–5 business days."
            );

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return back()->with('error', 'Stripe error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function chargeInvoice(Request $request, Client $client, XeroInvoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01', "max:{$invoice->amount_due}"],
        ]);

        // Guards
        if (! $client->stripe_customer_id || ! $client->stripe_payment_method_id) {
            return back()->with('error', 'This client has no BECS direct debit payment method on file.');
        }

        $xeroContact = $client->xeroContacts->first();
        if (! $xeroContact || $invoice->xero_contact_xero_id !== $xeroContact->xero_contact_id) {
            return back()->with('error', 'Invoice does not belong to this client.');
        }

        if (! in_array($invoice->status, ['AUTHORISED'])) {
            return back()->with('error', "Invoice is {$invoice->status} — only AUTHORISED invoices can be charged.");
        }

        if ($invoice->amount_due <= 0) {
            return back()->with('error', 'Invoice has no outstanding balance.');
        }

        $existing = $invoice->directDebitPayments()
            ->whereIn('status', ['pending', 'processing', 'settled'])
            ->first();

        if ($existing) {
            return back()->with('error',
                "A payment is already {$existing->status} for this invoice (ID: {$existing->id})."
            );
        }

        $ddPayment = DirectDebitPayment::create(
            DirectDebitPayment::dataFromInvoice(
                invoice:           $invoice,
                initiatedByType:   'manual',
                initiatedByUserId: $request->user()->id,
                overrideAmount:    isset($data['amount']) ? (float) $data['amount'] : null,
                client: $client
            )
        );

        ProcessSingleDirectDebit::dispatch($ddPayment->id)
            ->onQueue('payments');

        return back()->with('success',
            "Direct debit of \${$ddPayment->amount} {$ddPayment->currency_code} initiated for invoice {$invoice->xero_invoice_number}. BECS settles in 1–2 business days."
        );
    }

}
