<?php

namespace App\Http\Controllers;

use App\Mail\NewClientCreated;
use App\Models\Client;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\Stripe;

class OnboardingController extends Controller
{
    public function __construct(protected StripeService $stripe) {}
    public function show()
    {
        return view('welcome');
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required',
        ]);
        $data = $request->all();

        $staffFilePath = null;
        if ($request->hasFile('staff_contacts_file')) {
            $staffFilePath = $request->file('staff_contacts_file')->store('staff-contacts', 'public');
        }

        $websites = collect($data['websites'] ?? [])->filter()->values()->toArray();
        $serviceProviders = collect($data['service_providers'] ?? [])->filter()->values()->toArray();

        $client = Client::create([
            'company_name'   => $data['company_name']   ?? null,
            'company_phone'   => $data['company_phone']   ?? null,
            'industry'       => $data['industry']       ?? null,
            'website'        =>  !empty($websites) ? json_encode($websites) : null,
            'address'        => $data['address']        ?? null,
            'address_second'        => $data['address_second']        ?? null,
            'city'           => $data['city']           ?? null,
            'state'           => $data['state']           ?? null,
            'post_code'           => $data['post_code']           ?? null,
            'country'        => $data['country']        ?? null,
            'abn'            => $data['abn']            ?? null,
            'bank_name'            => $data['bank_name']            ?? null,
            'bank_branch'            => $data['bank_branch']            ?? null,
            'account_number'            => $data['account_number']            ?? null,
            'account_name'            => $data['account_name']            ?? null,
            'bsb'            => $data['bsb']            ?? null,
            'instagram'      => $data['instagram']      ?? null,
            'facebook'       => $data['facebook']       ?? null,
            'tiktok'         => $data['tiktok']         ?? null,
            'linkedin'       => $data['linkedin']       ?? null,
            'twitter'        => $data['twitter']        ?? null,
            'whatsapp_group' => $data['whatsapp_group'] ?? null,
            'logo_path'      => $data['logo_path']      ?? null,
            'contacts_file_path' => $staffFilePath,
            'pasted_employees'   => $data['pasted_employees'] ?? null,

            'notes'          => $data['notes']          ?? null,
            'services'          => $data['services']          ?? null,
            'service_providers'          => isset($serviceProviders) ? json_encode($serviceProviders)   : null,
            'status'         => 'active',
            'mandate_status'      => 'pending',
            'stripe_payment_method_id' => $data['stripe_payment_method_id'],
        ]);

        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $index => $contact) {
                $filled = array_filter(array_diff_key($contact, ['contact_type' => true, 'is_primary' => true]));
                if (!empty($filled)) {
                    $client->contacts()->create([
                        'full_name'    => $contact['full_name']    ?? null,
                        'role'         => $contact['role']         ?? null,
                        'contact_type' => $contact['contact_type'] ?? null,
                        'email'        => $contact['email']        ?? null,
                        'phone'        => $contact['phone']        ?? null,
                        'whatsapp'     => $contact['whatsapp']     ?? null,
                        'linkedin_url' => $contact['linkedin_url'] ?? null,
                        'email_opt_in' => $contact['email_opt_in'] ?? 0,
                        'sms_opt_in'   => $contact['sms_opt_in']   ?? 0,
                        'is_primary'   => $index === 0,
                    ]);
                }
            }
        }

        if (!empty($data['employees'])) {
            foreach ($data['employees'] as $employee) {
                if (array_filter($employee)) {
                    $client->contacts()->create([
                        'full_name'    => $employee['name']  ?? null,
                        'email'        => $employee['email'] ?? null,
                        'phone'        => $employee['phone'] ?? null,
                        'contact_type' => 'Employee',
                        'is_primary'   => false,
                    ]);
                }
            }
        }

        if (!empty($data['pasted_employees'])) {

            $lines = preg_split('/\r\n|\r|\n/', $data['pasted_employees']);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Split by space or comma
                $parts = preg_split('/[\s,|]+/', $line);

                $firstName = $parts[0] ?? null;
                $lastName  = $parts[1] ?? null;
                $email     = filter_var($parts[2] ?? null, FILTER_VALIDATE_EMAIL) ? $parts[2] : null;
                $phone     = $parts[3] ?? null;

                if ($email || $phone) {
                    $client->contacts()->create([
                        'full_name'    => trim("$firstName $lastName"),
                        'email'        => $email,
                        'phone'        => $phone,
                        'contact_type' => 'Employee',
                        'is_primary'   => false,
                    ]);
                }
            }
        }


        if ($request->filled('stripe_payment_method_id')) {
            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

                $paymentMethodId = $request->input('stripe_payment_method_id');
                $customerId      = $request->input('stripe_customer_id');

                if (!$customerId) {
                    throw new \Exception('Missing Stripe customer_id');
                }

                $customer = $stripe->customers->retrieve($customerId);

                $paymentMethod = $stripe->paymentMethods->retrieve($paymentMethodId);

                if (!$paymentMethod->customer) {
                    $stripe->paymentMethods->attach(
                        $paymentMethodId,
                        ['customer' => $customerId]
                    );
                } elseif ($paymentMethod->customer !== $customerId) {
                    throw new \Exception('PaymentMethod belongs to a different customer');
                }

                $client->update([
                    'stripe_customer_id'       => $customerId,
                    'stripe_payment_method_id' => $paymentMethodId,
                    'mandate_status'           => 'active',
                ]);

            } catch (\Exception $e) {
                \Log::error('Stripe error: ' . $e->getMessage());

                $client->update([
                    'mandate_status' => 'failed'
                ]);
            }
        }

        Mail::to('alit@allinit.com.au')
            ->cc('kapils@allinit.com.au')
            ->queue(new NewClientCreated($client));


        return redirect()->route('onboarding.thanks');
    }

    public function thanks()
    {
        return view('clients.thanks');
    }
    public function createSetupIntent(Request $request)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $customer = Customer::create([
                'name'  => $request->company_name ?? 'Unknown',
                'email' => $request->billing_email ?? null,
            ]);

            $setupIntent = SetupIntent::create([
                'customer'             => $customer->id,
                'payment_method_types' => ['au_becs_debit'],
            ]);

            return response()->json([
                'client_secret' => $setupIntent->client_secret,
                'customer_id'   => $customer->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Stripe SetupIntent error: ' . $e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
