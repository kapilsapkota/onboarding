<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\XeroConnection;
use App\Services\XeroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            $tokens   = $this->xero->exchangeCode($request->get('code'));
            $xeroUser = $this->decodeIdToken($tokens['id_token'] ?? null);
            $tenants  = $this->xero->getTenants($tokens['access_token']);
            $userId   = Auth::id();

            $connection = DB::transaction(function () use ($tokens, $xeroUser, $tenants, $userId) {

                // 1. Fetch the absolute first record in the table to guarantee a single row total
                $conn = XeroConnection::first();

                // 2. Prepare the fresh authorization dataset
                $data = [
                    'user_id'          => $userId, // Tracks which admin updated it last
                    'access_token'     => $tokens['access_token'],
                    'refresh_token'    => $tokens['refresh_token'],
                    'token_expires_at' => now()->addSeconds($tokens['expires_in']),
                    'is_active'        => true,
                    'xero_user_id'     => $xeroUser['xero_userid'] ?? $xeroUser['sub'] ?? null,
                    'xero_user_email'  => $xeroUser['email'] ?? null,
                    'xero_user_name'   => trim(($xeroUser['given_name'] ?? '') . ' ' . ($xeroUser['family_name'] ?? '')) ?: ($xeroUser['name'] ?? null),
                ];

                if ($conn) {
                    // Overwrite the single row in place
                    $conn->update($data);
                } else {
                    // If table is completely empty, create the single row
                    $conn = XeroConnection::create($data);
                }

                // 3. Optional: Set existing tenants for this connection to inactive first
                // This ensures if a tenant was removed from the Xero App permissions, it updates properly.
                $conn->tenants()->update(['is_active' => false]);

                // 4. Create or Update tenants based strictly on tenant_id
                foreach ($tenants as $tenant) {
                    $conn->tenants()->updateOrCreate(
                        [
                            'tenant_id' => $tenant['tenantId'], // Unique key constraint check
                        ],
                        [
                            'tenant_name' => $tenant['tenantName'],
                            'tenant_type' => $tenant['tenantType'] ?? null,
                            'is_active'   => true, // Reactivates or marks new ones active
                        ]
                    );
                }

                return $conn;
            });

            Log::info('Xero connected', ['user_id' => $userId, 'tenants' => count($tenants)]);

            return redirect()->route('admin.xero.index')
                ->with('success', 'Xero connected successfully!');

        } catch (\Throwable $e) {
            Log::error('Xero callback failed', ['error' => $e->getMessage()]);

            return redirect()->route('admin.xero.index')
                ->with('error', 'Failed to connect to Xero: ' . $e->getMessage());
        }
    }
    private function decodeIdToken(?string $idToken): array
    {
        if (! $idToken) {
            return [];
        }

        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            return [];
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
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
            $connection->update(['token_expires_at' => now()->subMinute()]);

            $fresh    = $this->xero->refreshToken($connection);
            $tenants = $this->xero->getTenants($connection['access_token']);
            $userInfo = $this->xero->getUserInfo($fresh->access_token);
            if (! empty($userInfo)) {
                $connection->update([
                    'xero_user_id'    => $userInfo['sub'] ?? $connection->xero_user_id,
                    'xero_user_email' => $userInfo['email'] ?? $connection->xero_user_email,
                    'xero_user_name'  => trim(($userInfo['given_name'] ?? '') . ' ' . ($userInfo['family_name'] ?? ''))
                        ?: ($userInfo['name'] ?? $connection->xero_user_name),
                ]);
            }

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

}
