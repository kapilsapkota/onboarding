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
        // All links are nullable: existing rows keep working in legacy
        // (global-key) mode until they are backfilled to an account.
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
        });

        foreach (['stripe_customers', 'stripe_payment_methods', 'stripe_charge_batches', 'stripe_charge_batch_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('stripe_account_id')->nullable()->constrained('stripe_accounts')->nullOnDelete();
            });
        }

        Schema::table('direct_debit_payments', function (Blueprint $table) {
            $table->foreignId('stripe_account_id')->nullable()->constrained('stripe_accounts')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_debit_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stripe_account_id');
            $table->dropConstrainedForeignId('company_id');
        });

        foreach (['stripe_charge_batch_items', 'stripe_charge_batches', 'stripe_payment_methods', 'stripe_customers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('stripe_account_id');
            });
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
