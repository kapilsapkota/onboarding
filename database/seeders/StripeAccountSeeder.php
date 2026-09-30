<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\StripeAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the legacy Stripe account holding the original global credentials
 * and backfills every pre-multi-tenant row onto it.
 *
 * Safe to run multiple times and on a fresh database:
 * - no-ops when no STRIPE_SECRET is configured (nothing to migrate);
 * - reuses the existing legacy account instead of creating duplicates;
 * - only fills stripe_account_id/company_id where they are still null,
 *   so rows already assigned to a real account are never touched.
 *
 * Owner company resolution: LEGACY_STRIPE_COMPANY_ID env when set and valid,
 * otherwise the first company, otherwise none (account still works globally).
 */
class StripeAccountSeeder extends Seeder
{
    public function run(): void
    {
        $secret = config('services.stripe.secret');

        if (empty($secret)) {
            $this->command?->warn('StripeAccountSeeder: no STRIPE_SECRET configured, skipping.');

            return;
        }

        $account = StripeAccount::firstOrCreate(
            ['is_legacy' => true],
            [
                'company_id' => $this->resolveCompanyId(),
                'display_name' => 'Legacy global account',
                'publishable_key' => config('services.stripe.key'),
                'secret_key' => $secret,
                'webhook_secret' => config('services.stripe.webhook_secret'),
                'is_default' => true,
                'status' => 'active',
            ]
        );

        // Keep the legacy credentials in sync with env on every run.
        $account->forceFill([
            'publishable_key' => config('services.stripe.key') ?: $account->publishable_key,
            'secret_key' => $secret,
            'webhook_secret' => config('services.stripe.webhook_secret') ?: $account->webhook_secret,
        ])->save();

        if ($account->company_id) {
            $account->setAsDefault();
        }

        $this->backfill($account);
    }

    private function resolveCompanyId(): ?int
    {
        $configured = (int) (env('LEGACY_STRIPE_COMPANY_ID') ?: 0);

        if ($configured > 0 && Company::where('id', $configured)->exists()) {
            return $configured;
        }

        return Company::orderBy('id')->value('id');
    }

    private function backfill(StripeAccount $account): void
    {
        foreach (['stripe_customers', 'stripe_payment_methods', 'stripe_charge_batches', 'stripe_charge_batch_items', 'direct_debit_payments', 'stripe_payouts', 'stripe_balance_transactions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'stripe_account_id')) {
                DB::table($table)->whereNull('stripe_account_id')->update(['stripe_account_id' => $account->id]);
            }
        }

        // Denormalize company onto direct debit payments via their client.
        // Done per-client (not a join update) so it works on MySQL and SQLite.
        if (Schema::hasColumn('direct_debit_payments', 'company_id')) {
            DB::table('clients')->whereNotNull('company_id')->select('id', 'company_id')
                ->orderBy('id')->chunk(500, function ($clients): void {
                    foreach ($clients as $client) {
                        DB::table('direct_debit_payments')
                            ->where('client_id', $client->id)
                            ->whereNull('company_id')
                            ->update(['company_id' => $client->company_id]);
                    }
                });
        }
    }
}
