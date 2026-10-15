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
        Schema::table('xero_tenants', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('xero_connection_id')->constrained('companies')->nullOnDelete();
            $table->foreignId('default_stripe_account_id')->nullable()->after('company_id')->constrained('stripe_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xero_tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_stripe_account_id');
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
