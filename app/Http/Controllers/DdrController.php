<?php

namespace App\Http\Controllers;

use App\Mail\DirectDebitConfigured;
use App\Models\Client;
use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Services\StripeAccountResolver;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Public direct-debit-request forms scoped to a single Stripe account.
 *
 * The account is resolved server-side from the link's public uuid only.
 * All Stripe calls in this controller run against the resolved account's
 * own credentials — never the global keys and never ids from the browser.
 *
 * The legacy global form (GET /ddr + onboarding routes) is untouched.
 */
class DdrController extends Controller
{
    public function __construct(private StripeAccountResolver $accounts) {}

    public function show(string $publicId)
    {
        $stripeAccount = $this->accounts->forPublicId($publicId);

        return view('ddr', compact('stripeAccount'));
    }

    public function setupIntent(Request $request, string $publicId)
    {
        try {
            $account = $this->accounts->forPublicId($publicId);
            $stripe = $this->accounts->clientFor($account);

            $customer = $stripe->customers->create([
                'name' => $request->company_name ?? 'Unknown',
                'email' => $request->billing_email ?? null,
            ]);

            $this->mirrorCustomer($account, $customer->id, (string) ($request->company_name ?? 'Unknown'), $request->billing_email);

            $setupIntent = $stripe->setupIntents->create([
                'customer' => $customer->id,
                'payment_method_types' => ['au_becs_debit'],
            ]);

            return response()->json([
                'client_secret' => $setupIntent->client_secret,
                'customer_id' => $customer->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Stripe SetupIntent error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, string $publicId)
    {
        $account = $this->accounts->forPublicId($publicId);

        $request->validate([
            'company_name' => 'required',
        ]);

        $data = $request->all();
        $stripe = $this->accounts->clientFor($account);

        $client = Client::create([
            'company_name' => $data['company_name'] ?? null,
            'company_id' => $account->company_id,
            'account_name' => $data['account_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'bsb' => $data['bsb'] ?? null,
            'status' => 'active',
            'mandate_status' => 'pending',
            'stripe_payment_method_id' => $data['stripe_payment_method_id'] ?? null,
        ]);

        $contact = $data['contacts'][0] ?? [];
        if (! empty($contact['full_name']) || ! empty($contact['email']) || ! empty($contact['phone'])) {
            $client->contacts()->create([
                'full_name' => $contact['full_name'] ?? null,
                'contact_type' => 'Main Contact',
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'is_primary' => true,
            ]);
        }

        if ($request->filled('stripe_payment_method_id')) {
            try {
                $paymentMethodId = $request->input('stripe_payment_method_id');
                $customerId = $request->input('stripe_customer_id');

                if (! $customerId) {
                    throw new \Exception('Missing Stripe customer_id');
                }

                $paymentMethod = $stripe->paymentMethods->retrieve($paymentMethodId);

                if (! $paymentMethod->customer) {
                    $stripe->paymentMethods->attach($paymentMethodId, ['customer' => $customerId]);
                    $paymentMethod = $stripe->paymentMethods->retrieve($paymentMethodId);
                } elseif ($paymentMethod->customer !== $customerId) {
                    throw new \Exception('PaymentMethod belongs to a different customer');
                }

                $client->update([
                    'stripe_customer_id' => $customerId,
                    'stripe_payment_method_id' => $paymentMethodId,
                    'mandate_status' => 'active',
                ]);

                $this->mirrorCustomer($account, $customerId, (string) ($data['company_name'] ?? ''), $contact['email'] ?? null);
                $this->mirrorPaymentMethod($account, $customerId, $paymentMethod);
            } catch (\Exception $e) {
                \Log::error('Stripe error: '.$e->getMessage());

                $client->update(['mandate_status' => 'failed']);
            }
        }

        Activity::record(
            description: 'New DDR submission for '.($client->company_name ?? 'unknown company').' on '.$account->display_name,
            subject: $client,
            event: 'submitted',
            properties: ['company_name' => $client->company_name, 'stripe_account_id' => $account->id],
            logName: 'onboarding',
        );

        Mail::to('alit@allinit.com.au')
            ->cc('kapils@allinit.com.au')
            ->queue(new DirectDebitConfigured(
                $client,
                $client->company ?? $account->company,
                $account->display_name,
            ));

        return redirect()->route('onboarding.thanks');
    }

    private function mirrorCustomer(StripeAccount $account, string $customerId, string $name, mixed $email): void
    {
        StripeCustomer::updateOrCreate(
            ['stripe_customer_id' => $customerId],
            [
                'stripe_account_id' => $account->id,
                'name' => $name ?: null,
                'email' => is_string($email) ? $email : null,
                'stripe_data' => [],
                'last_synced_at' => now(),
            ]
        );
    }

    private function mirrorPaymentMethod(StripeAccount $account, string $customerId, object $paymentMethod): void
    {
        $customer = StripeCustomer::where('stripe_customer_id', $customerId)->first();

        StripePaymentMethod::updateOrCreate(
            ['stripe_payment_method_id' => $paymentMethod->id],
            [
                'stripe_customer_id' => $customer?->id,
                'stripe_account_id' => $account->id,
                'type' => $paymentMethod->type ?? 'au_becs_debit',
                'last4' => $paymentMethod->au_becs_debit->last4 ?? null,
                'status' => 'active',
                'stripe_data' => method_exists($paymentMethod, 'toArray') ? $paymentMethod->toArray() : (array) $paymentMethod,
                'last_synced_at' => now(),
            ]
        );
    }
}
