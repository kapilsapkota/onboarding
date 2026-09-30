<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStripeAccountRequest;
use App\Http\Requests\Admin\UpdateStripeAccountRequest;
use App\Models\Company;
use App\Models\StripeAccount;
use Illuminate\Http\Request;

class StripeAccountController extends Controller
{
    public function index()
    {
        $this->authorizeAction('view-stripe-account');

        $accounts = StripeAccount::with('company')->withCount(['stripeCustomers', 'chargeBatches'])->orderBy('id')->get();
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return view('admin.stripe-accounts.index', compact('accounts', 'companies'));
    }

    public function store(StoreStripeAccountRequest $request)
    {
        $validated = $request->validated();

        $account = StripeAccount::create([
            'company_id' => $validated['company_id'] ?? null,
            'display_name' => $validated['display_name'],
            'publishable_key' => $validated['publishable_key'] ?? null,
            'secret_key' => $validated['secret_key'] ?? null,
            'webhook_secret' => $validated['webhook_secret'] ?? null,
            'status' => $validated['status'],
        ]);

        if ($request->boolean('is_default')) {
            $account->setAsDefault();
        }

        return back()->with('success', 'Stripe account created');
    }

    public function update(UpdateStripeAccountRequest $request, StripeAccount $stripeAccount)
    {
        $validated = $request->validated();

        // Secrets are write-only: only overwrite when a new value is supplied.
        $data = [
            'company_id' => $validated['company_id'] ?? null,
            'display_name' => $validated['display_name'],
            'status' => $validated['status'],
        ];

        foreach (['publishable_key', 'secret_key', 'webhook_secret'] as $keyField) {
            if ($request->filled($keyField)) {
                $data[$keyField] = $validated[$keyField];
            }
        }

        $stripeAccount->update($data);

        // The form always submits is_default (hidden 0 + checkbox).
        if ($request->boolean('is_default')) {
            $stripeAccount->setAsDefault();
        } elseif ($stripeAccount->is_default) {
            $stripeAccount->update(['is_default' => false]);
        }

        return back()->with('success', 'Stripe account updated');
    }

    public function destroy(Request $request, StripeAccount $stripeAccount)
    {
        $this->authorizeAction('delete-stripe-account');

        if ($stripeAccount->is_legacy) {
            return back()->withErrors(['account' => 'The legacy account cannot be deleted. Disable it instead.']);
        }

        if ($stripeAccount->stripeCustomers()->exists() || $stripeAccount->chargeBatches()->exists()) {
            return back()->withErrors(['account' => 'This account has customers or batches and cannot be deleted. Disable it instead.']);
        }

        $stripeAccount->delete();

        return back()->with('success', 'Stripe account deleted');
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
