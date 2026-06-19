<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\XeroTenant;
use App\Services\XeroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class XeroTenantSettingsController extends Controller
{
    public function edit(XeroTenant $tenant, XeroService $xero): View
    {
        $tenant->loadMissing('connection'); // ← required
        $bankAccounts = collect();
        $fetchError   = null;

        try {
            $bankAccounts = collect($xero->getBankAccounts($tenant));
        } catch (\Throwable $e) {
            $fetchError = 'Could not load bank accounts from Xero: ' . $e->getMessage();
        }

        return view('admin.xero.tenant-settings', compact('tenant', 'bankAccounts', 'fetchError'));
    }

    public function update(Request $request, XeroTenant $tenant, XeroService $xero): RedirectResponse
    {
        $tenant->loadMissing('connection'); // ← required
        $data = $request->validate([
            'dd_bank_account_id' => ['required', 'string'],
        ]);

        try {
            $bankAccounts = collect($xero->getBankAccounts($tenant));
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not verify bank accounts with Xero: ' . $e->getMessage());
        }

        $chosen = $bankAccounts->firstWhere('account_id', $data['dd_bank_account_id']);

        if (! $chosen) {
            return back()->withErrors(['dd_bank_account_id' => 'Selected account not found in Xero.']);
        }

        $tenant->update([
            'dd_bank_account_id'   => $chosen['account_id'],
            'dd_bank_account_name' => $chosen['name'],
        ]);

        return back()->with('success', "Direct debit bank account set to \"{$chosen['name']}\".");
    }
}
