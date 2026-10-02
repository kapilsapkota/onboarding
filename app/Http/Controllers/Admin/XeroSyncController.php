<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\XeroTenant;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class XeroSyncController extends Controller
{
    public function sync(Request $request, XeroTenant $tenant)
    {
        $type = $request->input('type');

        $mode = match ($type) {
            'contacts' => 'contacts',
            'invoices' => 'invoices',
            'repeating' => 'repeating',
            'all' => 'all',
            'full' => 'full',
            default => abort(400, 'Invalid sync type'),
        };

        Artisan::queue('xero:sync', [
            '--tenant' => $tenant->id,
            '--mode' => $mode,
        ]);

        Activity::record(
            description: "Queued Xero {$type} sync for tenant {$tenant->tenant_name}",
            subject: $tenant,
            event: 'sync',
            properties: ['mode' => $mode],
            logName: 'xero',
        );

        return back()->with('success', ucfirst($type).' sync has been queued.');
    }

    // Optional: direct run (if you still want manual sync button)
    public function syncNow(Request $request, XeroTenant $tenant)
    {
        $type = $request->input('type');

        $mode = match ($type) {
            'contacts' => 'contacts',
            'invoices' => 'invoices',
            'repeating' => 'repeating',
            'all' => 'all',
            'full' => 'full',
            default => abort(400, 'Invalid sync type'),
        };

        Artisan::call('xero:sync', [
            '--tenant' => $tenant->id,
            '--mode' => $mode,
            '--now' => true,
        ]);

        return back()->with('success', ucfirst($type).' sync executed.');
    }
}
