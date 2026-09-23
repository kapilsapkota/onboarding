<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stripe_charge_batch_items', function (Blueprint $table) {
            $table->string('stripe_charge_id')
                ->nullable()
                ->index()
                ->after('stripe_payment_intent_id');

            $table->string('stripe_balance_transaction_id')
                ->nullable()
                ->index()
                ->after('stripe_charge_id');
            $table->unsignedBigInteger('gross_amount')
                ->nullable()
                ->after('amount');

            $table->unsignedBigInteger('fee_amount')
                ->default(0)
                ->after('gross_amount');

            $table->bigInteger('net_amount')
                ->nullable()
                ->after('fee_amount');
            $table->foreignId('stripe_payout_id')
                ->nullable()
                ->after('net_amount')
                ->constrained('stripe_payouts')
                ->nullOnDelete();

            $table->timestamp('charged_at')
                ->nullable()
                ->after('processed_at');

            $table->timestamp('reconciled_at')
                ->nullable()
                ->after('charged_at');

            $table->string('reconciliation_status')
                ->default('unreconciled')
                ->index()
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_charge_batch_items', function (Blueprint $table) {
            //
        });
    }
};
