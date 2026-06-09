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
        Schema::create('xero_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('xero_tenant_id')->constrained()->cascadeOnDelete();

            $table->string('xero_contact_id')->index();
            $table->string('xero_contact_number')->nullable();
            $table->string('xero_account_number')->nullable();
            $table->string('xero_contact_status')->nullable();

            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('bank_account_details')->nullable();
            $table->string('company_number')->nullable();
            $table->string('tax_number')->nullable()->comment('ABN');
            $table->string('tax_number_type')->nullable();
            $table->string('accounts_receivable_tax_type')->nullable();
            $table->string('accounts_payable_tax_type')->nullable();
            $table->json('addresses')->nullable();
            $table->json('phones')->nullable();
            $table->boolean('is_supplier')->default(false);
            $table->boolean('is_customer')->default(false);
            $table->string('default_currency')->nullable();

            $table->timestamp('xero_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('match_score', 5, 2)->nullable();
            $table->string('match_method')->nullable();
            $table->boolean('is_matched')->default(false);

            $table->timestamps();

            $table->unique(['xero_tenant_id', 'xero_contact_id']);
            $table->index('email');
            $table->index('name');
            $table->index('tax_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xero_contacts');
    }
};
