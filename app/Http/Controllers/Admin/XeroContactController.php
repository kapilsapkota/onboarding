<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncXeroTenantContacts;
use App\Models\Client;
use App\Models\XeroConnection;
use App\Models\XeroContact;
use App\Models\XeroTenant;
use App\Services\XeroMatchService;
use App\Services\XeroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XeroContactController extends Controller
{
    public function __construct(private readonly XeroService $xero) {}

    public function contacts(XeroConnection $xeroConnection, XeroTenant $tenant, XeroMatchService $matcher)
    {
        $contacts  = $this->xero->getContacts($xeroConnection, $tenant);
        $customers = Client::all();

        $stored = XeroContact::forTenant($tenant->id)
            ->with('client')
            ->get()
            ->keyBy('xero_contact_id');

        $contacts = collect($contacts)->map(function ($contact) use ($matcher, $customers, $stored) {
            $contact['match']  = $matcher->matchContact($contact, $customers);
            $contact['stored'] = $stored->get($contact['ContactID']);
            return $contact;
        });

        $xeroTenant = $tenant;

        return view('admin.xero.contacts', compact(
            'contacts',
            'customers',
            'xeroTenant',
        ));
    }

    public function syncTenant(
        XeroTenant $tenant
    )
    {
        dispatch(
            new SyncXeroTenantContacts(
                $tenant->xero_connection_id,
                $tenant->id
            )
        );

        return back()->with(
            'success',
            'Contact sync job dispatched. Please check back later.'
        );
    }


    public function assign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contact_id'  => ['required', 'string'],
            'customer_id' => ['required', 'integer', 'exists:clients,id'],
            'tenant_id'   => ['required', 'integer', 'exists:xero_tenants,id'],
        ]);

        $xeroContact = XeroContact::where('xero_contact_id', $data['contact_id'])
            ->where('xero_tenant_id', $data['tenant_id'])
            ->firstOrFail();

        $client = Client::findOrFail($data['customer_id']);

        if(!$xeroContact || !$client){
            return response()->json(['ok' => false, 'message' => 'No matching contacts found.'], 404);
        }

        $xeroContact->markMatched($client, score: 100, method: 'manual');

        Log::info('Xero contact manually assigned', [
            'xero_contact_id' => $xeroContact->id,
            'client_id'       => $client->id,
        ]);

        return response()->json([
            'ok'      => true,
            'message' => "{$xeroContact->name} assigned to {$client->company_name}.",
        ]);
    }

    public function bulkAssign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'assignments'               => ['required', 'array', 'min:1', 'max:200'],
            'assignments.*.contact_id'  => ['required', 'string'],
            'assignments.*.customer_id' => ['required', 'integer', 'exists:clients,id'],
            'tenant_id'                 => ['required', 'integer', 'exists:xero_tenants,id'],
        ]);

        $pairs = collect($data['assignments'])->keyBy('contact_id');

        $xeroContacts = XeroContact::whereIn('xero_contact_id', $pairs->keys())
            ->where('xero_tenant_id', $data['tenant_id'])
            ->get();

        if ($xeroContacts->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'No matching contacts found.'], 404);
        }

        // Fetch only the clients that are actually referenced — not Client::all().
        $clients = Client::whereIn('id', $pairs->pluck('customer_id'))
            ->get()
            ->keyBy('id');

        $assigned = 0;
        $skipped  = 0;
        $failed   = 0;

        DB::transaction(function () use ($xeroContacts, $pairs, $clients, &$assigned, &$skipped, &$failed) {
            foreach ($xeroContacts as $xeroContact) {
                try {
                    $pair   = $pairs->get($xeroContact->xero_contact_id);
                    $client = $clients->get($pair['customer_id']);

                    if (!$client) {
                        $skipped++;
                        continue;
                    }

                    $xeroContact->markMatched($client, score: 100, method: 'manual');

                    $assigned++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Bulk assign failed for contact', [
                        'xero_contact_id' => $xeroContact->xero_contact_id,
                        'error'           => $e->getMessage(),
                    ]);
                }
            }
        });

        return response()->json([
            'ok'       => true,
            'assigned' => $assigned,
            'skipped'  => $skipped,
            'failed'   => $failed,
            'message'  => "Assigned {$assigned}, skipped {$skipped}, failed {$failed}.",
        ]);
    }

    public function clearMatch(XeroContact $xeroContact): JsonResponse
    {
        $xeroContact->clearMatch();

        return response()->json([
            'ok'      => true,
            'message' => "Match cleared for {$xeroContact->name}.",
        ]);
    }

}
