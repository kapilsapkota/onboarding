<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes type mismatches found against real Stripe data:
 *
 *  - fee can be NEGATIVE (e.g. payment_failure_refund carries fee=-130,
 *    amount=-10000, net=-9870). `unsignedBigInteger` throws 1264 Out of range.
 *  - batch item gross/fee can inherit those negatives via linkBatchItem().
 *  - descriptions / failure messages can exceed 255 chars -> TEXT.
 *  - missing Stripe fields: balance_type + exchange_rate (balance tx),
 *    failure_balance_transaction (payout).
 *
 * Uses raw MODIFY (no doctrine/dbal dependency) + hasColumn guards so it
 * runs cleanly on existing DBs and on fresh `migrate` runs.
 */
return new class extends Migration {
    public function up(): void
    {
        // --- signed money columns (negative refunds/fees must fit) ---
        DB::statement('ALTER TABLE `stripe_balance_transactions` MODIFY `fee` BIGINT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `stripe_balance_transactions` MODIFY `amount` BIGINT NOT NULL');
        DB::statement('ALTER TABLE `stripe_balance_transactions` MODIFY `net` BIGINT NOT NULL');
        DB::statement('ALTER TABLE `stripe_charge_batch_items` MODIFY `gross_amount` BIGINT NULL');
        DB::statement('ALTER TABLE `stripe_charge_batch_items` MODIFY `fee_amount` BIGINT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `stripe_charge_batch_items` MODIFY `net_amount` BIGINT NULL');

        // --- long text columns ---
        DB::statement('ALTER TABLE `stripe_balance_transactions` MODIFY `description` TEXT NULL');
        DB::statement('ALTER TABLE `stripe_payouts` MODIFY `description` TEXT NULL');

        // --- missing Stripe fields ---
        Schema::table('stripe_balance_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('stripe_balance_transactions', 'balance_type')) {
                $table->string('balance_type')->nullable()->after('status');
            }
            if (! Schema::hasColumn('stripe_balance_transactions', 'exchange_rate')) {
                // Stripe sends float|string|null — string preserves the exact value.
                $table->string('exchange_rate')->nullable()->after('balance_type');
            }
        });

        Schema::table('stripe_payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('stripe_payouts', 'failure_balance_transaction_stripe_id')) {
                $table->string('failure_balance_transaction_stripe_id')->nullable()->after('failure_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stripe_balance_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('stripe_balance_transactions', 'balance_type')) {
                $table->dropColumn('balance_type');
            }
            if (Schema::hasColumn('stripe_balance_transactions', 'exchange_rate')) {
                $table->dropColumn('exchange_rate');
            }
        });

        Schema::table('stripe_payouts', function (Blueprint $table) {
            if (Schema::hasColumn('stripe_payouts', 'failure_balance_transaction_stripe_id')) {
                $table->dropColumn('failure_balance_transaction_stripe_id');
            }
        });

        // Money/text columns are intentionally NOT reverted: going back to
        // unsigned/varchar would reintroduce the 1264 failures on real data.
    }
};
