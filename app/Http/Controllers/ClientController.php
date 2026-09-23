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
    public function index(Request $request): View
    {
        $perPage = $request->input('per_page', 25);

        if ($perPage !== 'all') {
            $perPage = in_array((int)$perPage, [25, 50, 100, 250], true)
                ? (int)$perPage
                : 25;
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */
        $sort = $request->input('sort', 'created_desc');

        $sortMap = [
            'created_desc' => ['clients.created_at', 'desc'],
            'created_asc' => ['clients.created_at', 'asc'],

            'name_asc' => ['clients.company_name', 'asc'],
            'name_desc' => ['clients.company_name', 'desc'],

            'industry_asc' => ['clients.industry', 'asc'],
            'industry_desc' => ['clients.industry', 'desc'],

            'status_asc' => ['clients.status', 'asc'],
            'status_desc' => ['clients.status', 'desc'],

            'email_asc' => ['primary_contact_email', 'asc'],
            'email_desc' => ['primary_contact_email', 'desc'],

            'phone_asc' => ['primary_contact_phone', 'asc'],
            'phone_desc' => ['primary_contact_phone', 'desc'],

            'contact_asc' => ['primary_contact_name', 'asc'],
            'contact_desc' => ['primary_contact_name', 'desc'],

            'xero_asc' => ['xero_count', 'asc'],
            'xero_desc' => ['xero_count', 'desc'],

            'stripe_asc' => ['stripe_connected', 'asc'],
            'stripe_desc' => ['stripe_connected', 'desc'],
        ];

        if (!isset($sortMap[$sort])) {
            $sort = 'created_desc';
        }

        [$sortColumn, $sortDirection] = $sortMap[$sort];

        /*
        |--------------------------------------------------------------------------
        | Client Query
        |--------------------------------------------------------------------------
        */
        $query = Client::query()
            ->with([
                'contacts',
                'xeroContacts',
            ])
            ->withCount([
                'xeroContacts',
            ])
            ->withMax([
                'contacts as primary_contact_name' => function ($query) {
                    $query->where('is_primary', true);
                },
            ], 'full_name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('company_name', 'like', "%{$search}%")
                        ->orWhere('industry', 'like', "%{$search}%")
                        ->orWhereHas('contacts', function ($query) use ($search) {
                            $query
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $request->filled('status'),
                fn($query) => $query->where(
                    'status',
                    $request->input('status')
                )
            )
            ->when(
                $request->filled('industry'),
                fn($query) => $query->where(
                    'industry',
                    $request->input('industry')
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Sorting that requires relationships
        |--------------------------------------------------------------------------
        */
        switch ($sort) {
            case 'email_asc':
                $query->orderBy(
                    ClientContact::select('email')
                        ->whereColumn('client_id', 'clients.id')
                        ->orderByDesc('is_primary')
                        ->limit(1),
                    'asc'
                );
                break;

            case 'email_desc':
                $query->orderBy(
                    ClientContact::select('email')
                        ->whereColumn('client_id', 'clients.id')
                        ->orderByDesc('is_primary')
                        ->limit(1),
                    'desc'
                );
                break;

            case 'phone_asc':
                $query->orderBy(
                    ClientContact::select('phone')
                        ->whereColumn('client_id', 'clients.id')
                        ->orderByDesc('is_primary')
                        ->limit(1),
                    'asc'
                );
                break;

            case 'phone_desc':
                $query->orderBy(
                    ClientContact::select('phone')
                        ->whereColumn('client_id', 'clients.id')
                        ->orderByDesc('is_primary')
                        ->limit(1),
                    'desc'
                );
                break;

            case 'contact_asc':
                $query->orderBy(
                    ClientContact::select('full_name')
                        ->whereColumn('client_id', 'clients.id')
                        ->orderByDesc('is_primary')
                        ->limit(1),
                    'asc'
                );
                break;

            case 'contact_desc':
                $query->orderBy(
                    ClientContact::select('full_name')
                        ->whereColumn('client_id', 'clients.id')
                        ->orderByDesc('is_primary')
                        ->limit(1),
                    'desc'
                );
                break;

            case 'xero_asc':
                $query->orderBy('xero_contacts_count', 'asc');
                break;

            case 'xero_desc':
                $query->orderBy('xero_contacts_count', 'desc');
                break;

            case 'stripe_asc':
                $query->orderByRaw("
                CASE
                    WHEN stripe_customer_id IS NOT NULL
                     AND stripe_customer_id != ''
                     AND stripe_payment_method_id IS NOT NULL
                     AND stripe_payment_method_id != ''
                    THEN 2

                    WHEN stripe_customer_id IS NOT NULL
                     AND stripe_customer_id != ''
                    THEN 1

                    ELSE 0
                END ASC
            ");
                break;

            case 'stripe_desc':
                $query->orderByRaw("
                CASE
                    WHEN stripe_customer_id IS NOT NULL
                     AND stripe_customer_id != ''
                     AND stripe_payment_method_id IS NOT NULL
                     AND stripe_payment_method_id != ''
                    THEN 2

                    WHEN stripe_customer_id IS NOT NULL
                     AND stripe_customer_id != ''
                    THEN 1

                    ELSE 0
                END DESC
            ");
                break;

            default:
                $query->orderBy($sortColumn, $sortDirection);
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
        $clients = $perPage === 'all'
            ? $query->get()
            : $query->paginate($perPage)->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Stats
        |--------------------------------------------------------------------------
        */
        $total = Client::count();

        $active = Client::where('status', 'active')->count();

        $inactive = Client::where('status', 'inactive')->count();

        $contacts = ClientContact::count();

        /*
        |--------------------------------------------------------------------------
        | Industries
        |--------------------------------------------------------------------------
        */
        $industries = Client::query()
            ->whereNotNull('industry')
            ->where('industry', '!=', '')
            ->distinct()
            ->orderBy('industry')
            ->pluck('industry');

        return view('clients.index', compact(
            'clients',
            'total',
            'active',
            'inactive',
            'contacts',
            'industries',
            'perPage',
            'sort'
        ));
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
        return \Carbon\Carbon::createFromTimestampMs((int)$matches[0]);
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
                'company_name' => $data['company_name'] ?? null,
                'industry' => $data['industry'] ?? null,
                'microsoft_tenant_url' => $data['microsoft_tenant_url'] ?? null,
                'website' => $data['website'] ?? null,
                'address_second' => $data['address_second'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'post_code' => $data['post_code'] ?? null,
                'abn' => $data['abn'] ?? null,
                'instagram' => $data['instagram'] ?? null,
                'facebook' => $data['facebook'] ?? null,
                'tiktok' => $data['tiktok'] ?? null,
                'linkedin' => $data['linkedin'] ?? null,
                'twitter' => $data['twitter'] ?? null,
                'whatsapp_group' => $data['whatsapp_group'] ?? null,
                'logo_path' => $data['logo_path'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'active',
                'bank_name' => $data['bank_name'] ?? null,
                'bank_branch' => $data['bank_branch'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'account_name' => $data['account_name'] ?? null,
                'bsb' => $data['bsb'] ?? null,
                'services' => $data['services'] ?? null,
                'service_providers' => isset($serviceProviders) ? json_encode($serviceProviders) : null,
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
                        'full_name' => $contact['full_name'] ?? null,
                        'role' => $contact['role'] ?? null,
                        'email' => $contact['email'] ?? null,
                        'phone' => $contact['phone'] ?? null,
                        'whatsapp' => $contact['whatsapp'] ?? null,
                        'linkedin_url' => $contact['linkedin_url'] ?? null,
                        'contact_type' => $contact['contact_type'] ?? null,
                        'birthday' => $contact['birthday'] ?? null,
                        'email_opt_in' => $contact['email_opt_in'] ?? 0,
                        'sms_opt_in' => $contact['sms_opt_in'] ?? 0,
                        'is_primary' => ($contact['contact_type'] ?? '') === 'Main Contact',
                    ]);
                }
            }

            // Employees
            foreach ($data['employees'] ?? [] as $employee) {
                if (array_filter($employee)) {
                    $client->contacts()->create([
                        'full_name' => $employee['name'] ?? null,
                        'email' => $employee['email'] ?? null,
                        'phone' => $employee['phone'] ?? null,
                        'contact_type' => 'Employee',
                        'is_primary' => false,
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

        if (!$dd) {
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
            'xero_contact_id' => $request->xero_contact_id,
            'xero_contact_name' => $request->xero_contact_name,
        ]);

        return back()->with('success', 'Xero contact assigned successfully.');
    }

    public function createPaymentMethodSetup(Request $request, Client $client)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
        ]);

        $stripe = new StripeClient(config('services.stripe.secret'));

        if ($client->stripe_customer_id) {
            // Update existing Stripe customer with latest details
            $stripeCustomer = $stripe->customers->update($client->stripe_customer_id, [
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
            ]);
        } else {
            // Create new Stripe customer
            $stripeCustomer = $stripe->customers->create([
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'metadata' => ['client_id' => (string)$client->id],
            ]);

            $client->update(['stripe_customer_id' => $stripeCustomer->id]);
        }

        StripeCustomer::updateOrCreate(
            ['stripe_customer_id' => $stripeCustomer->id],
            [
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'stripe_data' => $stripeCustomer->toArray(),
                'last_synced_at' => now(),
            ]
        );

        $setupIntent = $stripe->setupIntents->create([
            'customer' => $stripeCustomer->id,
            'usage' => 'off_session',
            'payment_method_types' => ['card', 'au_becs_debit'],
            'metadata' => ['client_id' => (string)$client->id],
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
        Client              $client,
        StripePaymentMethod $paymentMethod
    ): RedirectResponse
    {
        if (!$client->stripe_customer_id) {
            return back()->with('error', 'This client does not have a Stripe customer.');
        }

        if ($paymentMethod->stripeCustomer?->stripe_customer_id != $client->stripe_customer_id) {
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
                $client->load('stripeCustomer');
                $stripeCustomer = $client->stripeCustomer;
                if ($stripeCustomer) {
                    $stripeCustomer->paymentMethods()->update([
                        'is_default' => false,
                    ]);
                }

                // Make the selected method the default.
                $paymentMethod->update([
                    'is_default' => true,
                    'status' => 'active',
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

    public function export(Request $request)
    {
        $query = Client::query()
            ->with([
                'contacts',
                'xeroContacts',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('industry', 'like', "%{$search}%")
                    ->orWhere('billing_email', 'like', "%{$search}%")
                    ->orWhere('company_phone', 'like', "%{$search}%")
                    ->orWhere('abn', 'like', "%{$search}%")
                    ->orWhereHas('contacts', function ($contactQuery) use ($search) {
                        $contactQuery
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('industry')) {
            $query->where('industry', $request->input('industry'));
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        switch ($request->input('sort', 'created_desc')) {
            case 'name_asc':
                $query->orderBy('company_name', 'asc');
                break;

            case 'name_desc':
                $query->orderBy('company_name', 'desc');
                break;

            case 'industry_asc':
                $query->orderBy('industry', 'asc');
                break;

            case 'industry_desc':
                $query->orderBy('industry', 'desc');
                break;

            case 'status_asc':
                $query->orderBy('status', 'asc');
                break;

            case 'status_desc':
                $query->orderBy('status', 'desc');
                break;

            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;

            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Export
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | No pagination is used here.
        |
        */

        $clients = $query->get();

        $filename = 'clients-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($clients) {

            $handle = fopen('php://output', 'w');

            /*
            |--------------------------------------------------------------------------
            | Client columns
            |--------------------------------------------------------------------------
            |
            | Automatically get every attribute currently available on the model.
            |
            */

            $clientColumns = [
                'id',
                'company_name',
                'industry',
                'website',
                'address',
                'city',
                'country',
                'post_code',
                'abn',
                'billing_email',
                'monthly_budget',
                'referred_by',
                'instagram',
                'facebook',
                'tiktok',
                'linkedin',
                'twitter',
                'whatsapp_group',
                'logo_path',
                'contacts_file_path',
                'pasted_employees',
                'notes',
                'status',
                'company_phone',
                'address_second',
                'state',
                'service_providers',
                'services',
                'bank_name',
                'bank_branch',
                'account_name',
                'account_number',
                'bsb',
                'stripe_customer_id',
                'stripe_payment_method_id',
                'mandate_id',
                'mandate_status',
                'user_id',
                'microsoft_tenant_url',
                'created_at',
                'updated_at',
            ];

            /*
            |--------------------------------------------------------------------------
            | Contact columns
            |--------------------------------------------------------------------------
            */

            $contactColumns = [
                'id',
                'full_name',
                'email',
                'phone',
                'role',
                'is_primary',
            ];

            /*
            |--------------------------------------------------------------------------
            | Xero columns
            |--------------------------------------------------------------------------
            */

            $xeroColumns = [
                'id',
                'name',
                'email',
                'phone',
            ];

            /*
            |--------------------------------------------------------------------------
            | CSV Header
            |--------------------------------------------------------------------------
            */

            $headers = $clientColumns;

            foreach ($contactColumns as $column) {
                $headers[] = 'contact_' . $column;
            }

            foreach ($xeroColumns as $column) {
                $headers[] = 'xero_' . $column;
            }

            fputcsv($handle, $headers);

            /*
            |--------------------------------------------------------------------------
            | Rows
            |--------------------------------------------------------------------------
            */

            foreach ($clients as $client) {

                /*
                | Convert arrays/JSON into readable CSV values.
                */

                /*
 |--------------------------------------------------------------------------
 | Client data
 |--------------------------------------------------------------------------
 */

                $clientData = [];

                foreach ($clientColumns as $column) {

                    $value = $client->getAttribute($column);

                    /*
                    |--------------------------------------------------------------------------
                    | Convert arrays / JSON arrays to comma-separated values
                    |--------------------------------------------------------------------------
                    */

                    if (is_array($value)) {

                        $value = implode(', ', array_filter(
                            array_map(
                                fn($item) => is_scalar($item) ? (string)$item : json_encode($item),
                                $value
                            )
                        ));

                    } elseif (is_string($value)) {

                        /*
                        | Some database fields may contain JSON even if
                        | they are not cast as arrays in the model.
                        */

                        $decoded = json_decode($value, true);

                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {

                            $value = implode(', ', array_filter(
                                array_map(
                                    fn($item) => is_scalar($item) ? (string)$item : json_encode($item),
                                    $decoded
                                )
                            ));
                        }
                    }

                    $clientData[] = $value;
                }


                /*
                | Primary contact
                */

                $primary = $client->contacts->firstWhere('is_primary', true)
                    ?? $client->contacts->first();

                $contactData = [];

                foreach ($contactColumns as $column) {
                    $value = $primary?->getAttribute($column);

                    $contactData[] = $value;
                }

                /*
                | Xero contacts
                |
                | Multiple Xero contacts are stored in one CSV cell.
                */

                $xeroData = [];

                foreach ($xeroColumns as $column) {

                    $values = $client->xeroContacts
                        ->map(fn($xeroContact) => $xeroContact->getAttribute($column))
                        ->filter(fn($value) => $value !== null && $value !== '')
                        ->values()
                        ->all();

                    $xeroData[] = implode(' | ', $values);
                }

                fputcsv($handle, array_merge(
                    $clientData,
                    $contactData,
                    $xeroData
                ));
            }

            fclose($handle);

        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
