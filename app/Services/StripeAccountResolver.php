<?php

namespace App\Services;

use App\Models\StripeAccount;
use App\Models\StripeCustomer;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Stripe\StripeClient;

/**
 * Resolves which Stripe account to use from business context only.
 *
 * Routing inputs are always internal ids (company id, account id, DDR
 * public uuid, or a Stripe customer id looked up in our own mirror tables).
 * Request-supplied Stripe ids are NEVER used to pick credentials: any
 * customer/payment-method id must first be verified against the resolved
 * account's local records before an API call is made.
 *
 * When nothing resolves (e.g. rows not yet backfilled), the resolver falls
 * back to the legacy global keys so existing behaviour keeps working.
 */
class StripeAccountResolver
{
    /**
     * Default active account for a company, if one is configured.
     */
    public function forCompany(?int $companyId): ?StripeAccount
    {
        if ($companyId === null) {
            return null;
        }

        return StripeAccount::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * Account by internal id. Throws when unknown — ids must come from
     * server-side context (batch rows, session company), never the client.
     */
    public function forAccount(int $accountId): StripeAccount
    {
        return StripeAccount::find($accountId) ?? throw new ModelNotFoundException("Stripe account [{$accountId}] not found.");
    }

    /**
     * Account behind a public DDR link. Inactive accounts never resolve.
     */
    public function forPublicId(string $publicId): StripeAccount
    {
        return StripeAccount::where('public_id', $publicId)
            ->where('status', 'active')
            ->first() ?? throw new ModelNotFoundException('Stripe account link is invalid or disabled.');
    }

    /**
     * Account owning a Stripe customer id, via our own mirror table.
     * Returns null for pre-backfill customers (caller falls back to legacy).
     */
    public function forStripeCustomer(string $stripeCustomerId): ?StripeAccount
    {
        $customer = StripeCustomer::where('stripe_customer_id', $stripeCustomerId)
            ->whereNotNull('stripe_account_id')
            ->first();

        return $customer?->stripeAccount;
    }

    /**
     * Active accounts the user may view/charge through. Super-admins see
     * every account; members only see company-less accounts plus accounts
     * owned by their own companies. Users without companies see everything,
     * exactly as before multi-tenancy.
     *
     * @return Collection<int, StripeAccount>
     */
    public function visibleAccounts(?User $user): Collection
    {
        $query = StripeAccount::with('company')->where('status', 'active')->orderBy('id');

        if ($user && ! $user->hasRole('super-admin')) {
            $companyIds = $user->companies()->pluck('companies.id')->all();

            if (! empty($companyIds)) {
                $query->where(fn ($q) => $q->whereNull('company_id')->orWhereIn('company_id', $companyIds));
            }
        }

        return $query->get();
    }

    public function assertAccountVisible(?User $user, StripeAccount $account): void
    {
        abort_unless(
            $this->visibleAccounts($user)->contains('id', $account->id),
            403,
            'You cannot use this Stripe account.'
        );
    }

    /**
     * The seeded legacy account holding the original global credentials.
     */
    public function legacyAccount(): ?StripeAccount
    {
        return StripeAccount::where('is_legacy', true)->orderBy('id')->first();
    }

    /**
     * Stripe client for an account, or the legacy global client when null
     * (rows not yet migrated to an account keep working unchanged).
     */
    public function clientFor(?StripeAccount $account): StripeClient
    {
        $secret = $account?->secret_key ?: (string) config('services.stripe.secret');

        return new StripeClient($secret);
    }

    /**
     * Publishable key for Stripe.js, or the legacy global key when null.
     */
    public function publishableKeyFor(?StripeAccount $account): string
    {
        return $account?->publishable_key ?: (string) config('services.stripe.key');
    }
}
