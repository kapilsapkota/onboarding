<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xero_invoices', function (Blueprint $table) {
            $table->id();

            /*
            |------------------------------------------------------------------
            | Xero identifiers
            |------------------------------------------------------------------
            */
            $table->unsignedBigInteger('xero_tenant_id')->index();
            $table->uuid('xero_invoice_id')->unique();          // InvoiceID from Xero
            $table->string('xero_invoice_number')->nullable();  // INV-0001, BILL-0003, etc.
            $table->uuid('xero_branding_theme_id')->nullable();
            $table->string('type', 10)->index();
            $table->string('status', 20)->index();
            $table->unsignedBigInteger('xero_contact_id')->nullable()->index();
            $table->uuid('xero_contact_xero_id')->nullable()->index();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->date('fully_paid_on_date')->nullable();
            $table->string('reference')->nullable();
            $table->string('url')->nullable();
            $table->boolean('sent_to_contact')->default(false);
            $table->char('currency_code', 3)->nullable();
            $table->decimal('currency_rate', 15, 6)->nullable(); // to org base currency
            $table->decimal('sub_total', 15, 2)->nullable();
            $table->decimal('total_tax', 15, 2)->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->decimal('total_discount', 15, 2)->nullable();
            $table->decimal('amount_due', 15, 2)->nullable()->index();
            $table->decimal('amount_paid', 15, 2)->nullable();
            $table->decimal('amount_credited', 15, 2)->nullable();
            $table->json('line_items')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->timestamp('xero_updated_at')->nullable(); // UpdatedDateUTC from Xero
            $table->timestamp('last_synced_at')->nullable();  // when WE last fetched this

            $table->timestamp('payment_initiated_at')->nullable();
            $table->string('payment_method')->nullable();       // direct_debit | bank_transfer | card | bpay
            $table->string('payment_reference')->nullable();    // our internal batch / payment ID
            $table->string('payment_status', 20)->nullable()->index();
            $table->timestamp('payment_failed_at')->nullable();
            $table->text('payment_failure_reason')->nullable();
            $table->timestamp('payment_settled_at')->nullable();

            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->boolean('is_reconciled')->default(false)->index();
            $table->string('xero_repeating_invoice_id')->nullable();

            $table->timestamps();

            /*
            |------------------------------------------------------------------
            | Foreign keys
            |------------------------------------------------------------------
            */
            $table->foreign('xero_tenant_id')
                ->references('id')->on('xero_tenants')
                ->cascadeOnDelete();

            $table->foreign('xero_contact_id')
                ->references('id')->on('xero_contacts')
                ->nullOnDelete();

            $table->foreign('client_id')
                ->references('id')->on('clients')
                ->nullOnDelete();

            /*
            |------------------------------------------------------------------
            | Composite indexes
            |------------------------------------------------------------------
            */
            $table->index(['xero_tenant_id', 'status']);

            $table->index(['xero_tenant_id', 'due_date', 'amount_due']);
            $table->index(['payment_status', 'payment_initiated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xero_invoices');
    }
};
