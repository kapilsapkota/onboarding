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
        // Nullable like every other stripe link: pre-multi-tenant rows keep
        // working in legacy (global) mode until backfilled to an account.
        Schema::table('stripe_payouts', function (Blueprint $table) {
            $table->foreignId('stripe_account_id')->nullable()->constrained('stripe_accounts')->nullOnDelete();
        });

        Schema::table('stripe_balance_transactions', function (Blueprint $table) {
            $table->foreignId('stripe_account_id')->nullable()->constrained('stripe_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_balance_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stripe_account_id');
        });

        Schema::table('stripe_payouts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stripe_account_id');
        });
    }
};
