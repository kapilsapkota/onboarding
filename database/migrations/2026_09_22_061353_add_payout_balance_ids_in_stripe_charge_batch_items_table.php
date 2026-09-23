<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stripe_charge_batch_items', function (Blueprint $table) {

            if (!Schema::hasColumn('stripe_charge_batch_items', 'stripe_charge_id')) {
                $table->string('stripe_charge_id')
                    ->nullable()
                    ->index()
                    ->after('stripe_payment_intent_id');
            }

            if (!Schema::hasColumn('stripe_charge_batch_items', 'stripe_balance_transaction_id')) {
                $table->string('stripe_balance_transaction_id')
                    ->nullable()
                    ->index()
                    ->after('stripe_charge_id');
            }

            if (!Schema::hasColumn('stripe_charge_batch_items', 'gross_amount')) {
                $table->unsignedBigInteger('gross_amount')
                    ->nullable()
                    ->after('amount');
            }

            if (!Schema::hasColumn('stripe_charge_batch_items', 'fee_amount')) {
                $table->unsignedBigInteger('fee_amount')
                    ->default(0)
                    ->after('gross_amount');
            }

            if (!Schema::hasColumn('stripe_charge_batch_items', 'net_amount')) {
                $table->bigInteger('net_amount')
                    ->nullable()
                    ->after('fee_amount');
            }

            if (!Schema::hasColumn('stripe_charge_batch_items', 'stripe_payout_id')) {
                $table->foreignId('stripe_payout_id')
                    ->nullable()
                    ->after('net_amount')
                    ->constrained('stripe_payouts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('stripe_charge_batch_items', 'charged_at')) {
                $table->timestamp('charged_at')
                    ->nullable()
                    ->after('processed_at');
            }

            if (!Schema::hasColumn('stripe_charge_batch_items', 'reconciled_at')) {
                $table->timestamp('reconciled_at')
                    ->nullable()
                    ->after('charged_at');
            }

            if (!Schema::hasColumn('stripe_charge_batch_items', 'reconciliation_status')) {
                $table->string('reconciliation_status')
                    ->default('unreconciled')
                    ->index()
                    ->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_charge_batch_items', function (Blueprint $table) {

            if (Schema::hasColumn('stripe_charge_batch_items', 'stripe_payout_id')) {
                $table->dropForeign(['stripe_payout_id']);
            }

            $columns = [
                'stripe_charge_id',
                'stripe_balance_transaction_id',
                'gross_amount',
                'fee_amount',
                'net_amount',
                'stripe_payout_id',
                'charged_at',
                'reconciled_at',
                'reconciliation_status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('stripe_charge_batch_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
