<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\XeroConnection;
use App\Models\XeroTenant;
use App\Services\XeroMatchService;
use App\Services\XeroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class XeroConnectionController extends Controller
{
    public function __construct(private readonly XeroService $xero) {}

    /**
     * Redirect the admin to Xero's OAuth2 authorization page.
     */
    public function connect()
    {
        return redirect($this->xero->getAuthorizationUrl());
    }

    /**
     * Handle Xero OAuth callback, exchange code for tokens, and store tenants.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('admin.xero.index')
                ->with('error', 'Xero authorization was denied: ' . $request->get('error_description'));
        }

        if ($request->get('state') !== csrf_token()) {
            return redirect()->route('admin.xero.index')
                ->with('error', 'State mismatch. Please try again.');
        }

        try {
            $tokens  = $this->xero->exchangeCode($request->get('code'));
            $tenants = $this->xero->getTenants($tokens['access_token']);

            $connection = XeroConnection::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                ],
                [
                    'access_token'     => $tokens['access_token'],
                    'refresh_token'    => $tokens['refresh_token'],
                    'token_expires_at' => now()->addSeconds($tokens['expires_in']),
                    'is_active'        => true,
                ]
            );


            foreach ($tenants as $tenant) {
                $connection->tenants()->updateOrCreate(
                    [
                        'tenant_id' => $tenant['tenantId'],
                    ],
                    [
                        'tenant_name' => $tenant['tenantName'],
                        'tenant_type' => $tenant['tenantType'] ?? null,
                        'is_active'   => true,
                    ]
                );
            }


            Log::info('Xero connected', ['user_id' => Auth::id(), 'tenants' => count($tenants)]);

            return redirect()->route('admin.xero.index')
                ->with('success', 'Xero connected successfully!');
        } catch (\Throwable $e) {
            Log::error('Xero callback failed', ['error' => $e->getMessage()]);

            return redirect()->route('admin.xero.index')
                ->with('error', 'Failed to connect to Xero: ' . $e->getMessage());
        }
    }

    /**
     * Show the Xero connection management page.
     */
    public function index()
    {
        $connections = XeroConnection::where('user_id', Auth::id())
            ->with('tenants')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.xero.index', compact('connections'));
    }

    /**
     * Manually refresh a specific connection's token.
     */
    public function refresh(XeroConnection $connection)
    {
//        $this->authorize('update', $connection);

        try {
            $connection->token_expires_at = now()->subMinute();
            $connection->save();
            $this->xero->refreshToken($connection);

            $tenants = $this->xero->getTenants($connection['access_token']);
            foreach ($tenants as $tenant) {
                $connection->tenants()->updateOrCreate(
                    [
                        'tenant_id' => $tenant['tenantId'],
                    ],
                    [
                        'tenant_name' => $tenant['tenantName'],
                        'tenant_type' => $tenant['tenantType'] ?? null,
                        'is_active'   => true,
                    ]
                );
            }


            return back()->with('success', 'Token refreshed successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Token refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect a Xero tenant.
     */
    public function disconnect(XeroConnection $connection)
    {
//        $this->authorize('delete', $connection);

        $connection->update(['is_active' => false]);

        return back()->with('success', "Disconnected from {$connection->tenant_name}.");
    }

    public function contacts(XeroConnection $xeroConnection, XeroTenant $tenant, XeroMatchService $matcher)
    {
        $contacts = $this->xero->getContacts($xeroConnection, $tenant);

        $customers = Client::all();

        $contacts = collect($contacts)->map(function ($contact) use ($matcher, $customers) {

            $match = $matcher->matchContact($contact, $customers);

            $contact['match'] = $match;

            return $contact;
        });
        $xeroTenant = $tenant;

        return view('admin.xero.contacts', compact(
            'contacts',
            'customers',
            'xeroTenant'
        ));
    }
}
