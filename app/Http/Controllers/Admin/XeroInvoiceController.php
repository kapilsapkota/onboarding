<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTenantInvoiceJob;
use App\Models\XeroTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XeroInvoiceController extends Controller
{
    public function sync(Request $request)
    {
        try {
            $tenant = XeroTenant::with('connection')->where('is_active', true)->first();

            if (!$tenant || !$tenant->connection) {
                return redirect()->back()->with('error', 'No active Xero tenant connection found.');
            }

            $job = new SyncTenantInvoiceJob(
                connectionId:  $tenant->connection->id,
                tenantId:      $tenant->id,
                modifiedAfter: null,
                fullResync:    false,
            );

            dispatch_sync($job);

            return redirect()->back()->with('success', 'Invoices are being synced! Please check back later.');

        } catch (\Throwable $e) {
            Log::error('Direct Xero job sync failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
