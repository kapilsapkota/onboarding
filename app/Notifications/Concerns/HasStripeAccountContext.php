<?php

namespace App\Notifications\Concerns;

use App\Models\StripeAccount;

trait HasStripeAccountContext
{
    /**
     * Human label identifying which Stripe account (and company) a record
     * belongs to, e.g. "Primary AUD — Acme". Falls back to "Legacy pool"
     * for pre-multi-tenant rows without an account.
     */
    protected function stripeAccountLabel(?StripeAccount $account): string
    {
        if (! $account) {
            return 'Legacy pool';
        }

        $company = $account->company?->name;

        return $company ? "{$account->display_name} — {$company}" : $account->display_name;
    }

    protected function stripeAccountCompanyName(?StripeAccount $account): ?string
    {
        return $account?->company?->name;
    }
}
