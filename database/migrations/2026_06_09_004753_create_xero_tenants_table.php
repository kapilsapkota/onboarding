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
        Schema::create('xero_tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('xero_connection_id')
                ->constrained('xero_connections')
                ->cascadeOnDelete();

            $table->string('tenant_id')->index();
            $table->string('tenant_name');
            $table->string('tenant_type')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_contact_synced_at')->nullable();
            $table->timestamp('last_invoice_synced_at')->nullable();
            $table->timestamp('last_payment_synced_at')->nullable();
            $table->timestamp('last_repeating_invoice_synced_at')->nullable();

            $table->string('dd_bank_account_id')->nullable();
            $table->string('dd_bank_account_name')->nullable();

            $table->unique(['xero_connection_id', 'tenant_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xero_tenants');
    }
};
