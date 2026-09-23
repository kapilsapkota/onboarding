<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stripe_payouts', function (Blueprint $table) {
            $table->timestamp('stripe_created_at')->nullable()->after('stripe_payout_id');
            $table->string('reconciliation_status')->nullable()->after('status');
            $table->string('failure_code')->nullable()->after('reconciliation_status');
            $table->text('failure_message')->nullable()->after('failure_code');
            $table->string('statement_descriptor')->nullable()->after('description');
            $table->boolean('automatic')->default(true)->after('statement_descriptor');
            $table->string('trace_id')->nullable()->after('destination');
            $table->string('balance_transaction_stripe_id')->nullable()->after('trace_id');
        });

        Schema::table('stripe_balance_transactions', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('source_id');
            $table->string('description')->nullable()->after('reporting_category');
            $table->string('status')->nullable()->after('description');
            $table->string('payout_stripe_id')->nullable()->index()->after('stripe_payout_id');
            $table->string('charge_stripe_id')->nullable()->index()->after('payout_stripe_id');
            $table->string('payment_intent_stripe_id')->nullable()->index()->after('charge_stripe_id');
            $table->string('customer_stripe_id')->nullable()->index()->after('payment_intent_stripe_id');
            $table->boolean('is_app_transaction')->default(false)->index()->after('customer_stripe_id');
            $table->json('fee_details')->nullable()->after('stripe_data');
        });
    }

    public function down(): void
    {
        Schema::table('stripe_payouts', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_created_at',
                'reconciliation_status',
                'failure_code',
                'failure_message',
                'statement_descriptor',
                'automatic',
                'trace_id',
                'balance_transaction_stripe_id',
            ]);
        });

        Schema::table('stripe_balance_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'source_type',
                'description',
                'status',
                'payout_stripe_id',
                'charge_stripe_id',
                'payment_intent_stripe_id',
                'customer_stripe_id',
                'is_app_transaction',
                'fee_details',
            ]);
        });
    }
};
