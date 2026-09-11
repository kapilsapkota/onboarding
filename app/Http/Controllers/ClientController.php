<?php

namespace App\Http\Controllers;

use App\Jobs\WriteXeroPayment;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\XeroContact;
use App\Models\XeroInvoice;
use App\Models\XeroTenant;
use App\Services\XeroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Stripe\StripeClient;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with('contacts');

        // Search — company name, contact name, email, phone
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhereHas('contacts', function ($cq) use ($search) {
                        $cq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Industry filter
        if ($industry = $request->get('industry')) {
            $query->where('industry', $industry);
        }

        // Sorting
        switch ($request->get('sort', 'created_desc')) {
            case 'created_asc':
                $query->oldest();
                break;
            case 'name_asc':
                $query->orderBy('company_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('company_name', 'desc');
                break;
            default: // created_desc
                $query->latest();
                break;
        }

        $clients = $query->paginate(20)->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function show(Client $client): View
    {
        $client->load(['contacts', 'charges', 'xeroContacts']);

        $invoices = collect();
        $xeroContacts = XeroContact::with('tenant')->get();
        $xeroContact = $client->xeroContacts->first();

        if ($xeroContact) {
            $invoices = XeroInvoice::query()
                ->where('xero_contact_xero_id', $xeroContact->xero_contact_id)
                ->receivable()
                ->whereNotIn('status', ['DELETED', 'VOIDED'])
                ->with([
                    'latestDirectDebitPayment',
                    'repeatingInvoice',
                ])
                ->orderByDesc('invoice_date')
                ->get();
        }

        return view('clients.show', compact('client', 'invoices', 'xeroContacts', 'xeroContact'));
    }
    function xeroDateToCarbon(?string $xeroDate): ?\Carbon\Carbon
    {
        if (!$xeroDate) return null;

        preg_match('/\d+/', $xeroDate, $matches);

        if (!isset($matches[0])) return null;

        // Xero gives milliseconds
        return \Carbon\Carbon::createFromTimestampMs((int) $matches[0]);
    }

    public function edit(Client $client)
    {
        $client->load('contacts');
        $contacts = $client->contacts;

        return view('clients.edit', [
            'client' => $client,
            'mainContact' => $contacts->firstWhere('contact_type', 'Main Contact'),
            'financeContact' => $contacts->firstWhere('contact_type', 'Finance'),
            'techContact' => $contacts->firstWhere('contact_type', 'Technical'),
            'employees' => $contacts->where('contact_type', 'Employee'),
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->all();

        DB::transaction(function () use ($data, $client) {

            $client->update([
                'company_name'   => $data['company_name']   ?? null,
                'industry'       => $data['industry']       ?? null,
                'microsoft_tenant_url'       => $data['microsoft_tenant_url']       ?? null,
                'website'        => $data['website']        ?? null,
                'address_second'        => $data['address_second']        ?? null,
                'address'        => $data['address']        ?? null,
                'city'           => $data['city']           ?? null,
                'state'           => $data['state']           ?? null,
                'country'        => $data['country']        ?? null,
                'post_code'        => $data['post_code']        ?? null,
                'abn'            => $data['abn']            ?? null,
                'instagram'      => $data['instagram']      ?? null,
                'facebook'       => $data['facebook']       ?? null,
                'tiktok'         => $data['tiktok']         ?? null,
                'linkedin'       => $data['linkedin']       ?? null,
                'twitter'        => $data['twitter']        ?? null,
                'whatsapp_group' => $data['whatsapp_group'] ?? null,
                'logo_path'      => $data['logo_path']      ?? null,
                'notes'          => $data['notes']          ?? null,
                'status'         => $data['status']         ?? 'active',
                'bank_name'            => $data['bank_name']            ?? null,
                'bank_branch'            => $data['bank_branch']            ?? null,
                'account_number'            => $data['account_number']            ?? null,
                'account_name'            => $data['account_name']            ?? null,
                'bsb'            => $data['bsb']            ?? null,
                'services'          => $data['services']          ?? null,
                'service_providers'          => isset($serviceProviders) ? json_encode($serviceProviders)   : null,
            ]);

            $client->contacts()->delete();

            // Contacts (Main, Finance, Tech)
            foreach ($data['contacts'] ?? [] as $contact) {
                if (
                    !empty($contact['full_name']) ||
                    !empty($contact['email']) ||
                    !empty($contact['phone'])
                ) {
                    $client->contacts()->create([
                        'full_name'    => $contact['full_name'] ?? null,
                        'role'         => $contact['role'] ?? null,
                        'email'        => $contact['email'] ?? null,
                        'phone'        => $contact['phone'] ?? null,
                        'whatsapp'     => $contact['whatsapp'] ?? null,
                        'linkedin_url' => $contact['linkedin_url'] ?? null,
                        'contact_type' => $contact['contact_type'] ?? null,
                        'birthday'     => $contact['birthday'] ?? null,
                        'email_opt_in' => $contact['email_opt_in'] ?? 0,
                        'sms_opt_in'   => $contact['sms_opt_in'] ?? 0,
                        'is_primary'   => ($contact['contact_type'] ?? '') === 'Main Contact',
                    ]);
                }
            }

            // Employees
            foreach ($data['employees'] ?? [] as $employee) {
                if (array_filter($employee)) {
                    $client->contacts()->create([
                        'full_name'    => $employee['name'] ?? null,
                        'email'        => $employee['email'] ?? null,
                        'phone'        => $employee['phone'] ?? null,
                        'contact_type' => 'Employee',
                        'is_primary'   => false,
                    ]);
                }
            }

        });

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }
    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client removed.');
    }

    public function updateStatus(Request $request, Client $client)
    {
        $client->update(['status' => $request->status]);
        return back()->with('success', 'Status updated.');
    }

    public function syncXero(Client $client, XeroInvoice $invoice): RedirectResponse
    {
        $dd = $invoice->directDebitPayments()
            ->where('status', 'settled')
            ->whereNull('xero_payment_id')
            ->latest()
            ->first();

        if (! $dd) {
            return back()->with('error', 'No settled payment found that needs Xero sync.');
        }

        WriteXeroPayment::dispatch($dd->id)->onQueue('payments');

        return back()->with('success', "Xero sync queued for invoice {$invoice->xero_invoice_number}.");
    }

    public function assignXeroContact(Request $request, Client $client)
    {
        $request->validate([
            'xero_contact_id' => ['required', 'string'],
            'xero_contact_name' => ['nullable', 'string'],
        ]);

        $client->update([
            'xero_contact_id'   => $request->xero_contact_id,
            'xero_contact_name' => $request->xero_contact_name,
        ]);

        return back()->with('success', 'Xero contact assigned successfully.');
    }

public function createPaymentMethodSetup(Request $request, Client $client)
{
    $data = $request->validate([
        'customer_name'  => ['required', 'string', 'max:255'],
        'customer_email' => ['required', 'email', 'max:255'],
    ]);

    $stripe = new StripeClient(config('services.stripe.secret'));

    if ($client->stripe_customer_id) {
        // Update existing Stripe customer with latest details
        $stripeCustomer = $stripe->customers->update($client->stripe_customer_id, [
            'name'  => $data['customer_name'],
            'email' => $data['customer_email'],
        ]);
    } else {
        // Create new Stripe customer
        $stripeCustomer = $stripe->customers->create([
            'name'     => $data['customer_name'],
            'email'    => $data['customer_email'],
            'metadata' => ['client_id' => (string) $client->id],
        ]);

        $client->update(['stripe_customer_id' => $stripeCustomer->id]);
    }

    StripeCustomer::updateOrCreate(
        ['stripe_customer_id' => $stripeCustomer->id],
        [
            'name'           => $data['customer_name'],
            'email'          => $data['customer_email'],
            'stripe_data'    => $stripeCustomer->toArray(),
            'last_synced_at' => now(),
        ]
    );

    $setupIntent = $stripe->setupIntents->create([
        'customer'             => $stripeCustomer->id,
        'usage'                => 'off_session',
        'payment_method_types' => ['card', 'au_becs_debit'],
        'metadata'             => ['client_id' => (string) $client->id],
    ]);

    return response()->json(['client_secret' => $setupIntent->client_secret]);
}

public function storePaymentMethod(Request $request, Client $client)
    {
        $validated = $request->validate([
            'setup_intent_id' => ['required', 'string'],
            'make_default' => ['nullable', 'boolean'],
        ]);

        $stripe = new StripeClient(config('services.stripe.secret'));

        $setupIntent = $stripe->setupIntents->retrieve(
            $validated['setup_intent_id']
        );

        /*
         * Security check.
         *
         * Make sure this SetupIntent belongs to this client.
         */
        if ($setupIntent->customer !== $client->stripe_customer_id) {
            abort(403, 'Payment setup does not belong to this client.');
        }

        if ($setupIntent->status !== 'succeeded') {
            return back()->with(
                'error',
                'Payment method setup was not completed.'
            );
        }

        $paymentMethodId = $setupIntent->payment_method;

        if (!$paymentMethodId) {
            return back()->with(
                'error',
                'Stripe did not return a payment method.'
            );
        }

        $paymentMethod = $stripe->paymentMethods->retrieve(
            $paymentMethodId
        );

        /*
         * Get our local Stripe customer.
         */
        $stripeCustomer = StripeCustomer::firstOrCreate(
            [
                'stripe_customer_id' => $client->stripe_customer_id,
            ],
            [
                'name' => $client->company_name,
                'email' => $client->primary_email,
            ]
        );

        /*
         * Extract display information.
         */
        $last4 = null;
        $accountHolderName = $paymentMethod->billing_details->name ?? null;

        if ($paymentMethod->type === 'card') {
            $last4 = $paymentMethod->card->last4 ?? null;
        }

        if ($paymentMethod->type === 'au_becs_debit') {
            $last4 = $paymentMethod->au_becs_debit->last4 ?? null;
        }

        /*
         * Save the payment method locally.
         */
        $localPaymentMethod = StripePaymentMethod::updateOrCreate(
            [
                'stripe_payment_method_id' => $paymentMethod->id,
            ],
            [
                'stripe_customer_id' => $stripeCustomer->id,
                'type' => $paymentMethod->type,
                'last4' => $last4,
                'account_holder_name' => $accountHolderName,
                'is_default' => false,
                'status' => 'active',
                'stripe_data' => $paymentMethod->toArray(),
                'last_synced_at' => now(),
            ]
        );

        /*
         * Make it the default payment method if requested.
         */
        if ($request->boolean('make_default')) {

            $stripe->customers->update(
                $client->stripe_customer_id,
                [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethod->id,
                    ],
                ]
            );

            $stripeCustomer->paymentMethods()
                ->where('id', '!=', $localPaymentMethod->id)
                ->update([
                    'is_default' => false,
                ]);

            $localPaymentMethod->update([
                'is_default' => true,
            ]);

            $stripeCustomer->update([
                'default_payment_method_id' => $paymentMethod->id,
            ]);
        }

        return redirect()
            ->route('clients.show', $client)
            ->with(
                'success',
                'Payment method added successfully.'
            );
    }

    public function makeDefaultPaymentMethod(
        Client $client,
        StripePaymentMethod $paymentMethod
    ): RedirectResponse {
        if (!$client->stripe_customer_id) {
            return back()->with('error', 'This client does not have a Stripe customer.');
        }

        if ($paymentMethod->stripeCustomer?->stripe_customer_id != $client->stripe_customer_id){
            return back()->with('error', 'This payment method does not belong to this client.');
        }

        if ($paymentMethod->status !== 'active') {
            return back()->with('error', 'This payment method is not active.');
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            // Set the default payment method on the Stripe Customer.
            $stripe->customers->update(
                $client->stripe_customer_id,
                [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethod->stripe_payment_method_id,
                    ],
                ]
            );

            DB::transaction(function () use ($client, $paymentMethod) {
                // Remove default from all other methods belonging to this customer.
                StripePaymentMethod::where(
                    'stripe_customer_id',
                    $paymentMethod->stripe_customer_id
                )->update([
                    'is_default' => false,
                ]);

                // Make the selected method the default.
                $paymentMethod->update([
                    'is_default' => true,
                    'status'     => 'active',
                ]);

                // If your clients table uses this field for charging:
                $client->update([
                    'stripe_payment_method_id' => $paymentMethod->stripe_payment_method_id,
                ]);
            });

            return back()->with(
                'success',
                'Default payment method updated successfully.'
            );

        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Unable to change the default payment method.'
            );
        }
    }
}
