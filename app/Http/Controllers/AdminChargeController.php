<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientCharge;
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
}
