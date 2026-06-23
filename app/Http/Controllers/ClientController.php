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

        return view('clients.show', compact('client', 'invoices'));
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
}
