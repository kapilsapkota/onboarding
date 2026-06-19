<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_debit_payments', function (Blueprint $table) {
            $table->id();

            /*
            |------------------------------------------------------------------
            | What we're paying
            |------------------------------------------------------------------
            */
            $table->unsignedBigInteger('xero_invoice_id');  // FK → xero_invoices.id
            $table->unsignedBigInteger('xero_tenant_id');
            $table->unsignedBigInteger('client_id')->nullable();

            $table->uuid('xero_invoice_xero_id');           // raw Xero InvoiceID UUID
            $table->string('xero_invoice_number')->nullable();
            $table->decimal('amount', 15, 2);               // amount we attempted to collect
            $table->char('currency_code', 3)->default('AUD');

            /*
            |------------------------------------------------------------------
            | Direct debit / gateway details
            |------------------------------------------------------------------
            */
            $table->string('payment_method')->default('direct_debit');  // direct_debit | bpay | card
            $table->string('gateway')->nullable();                       // e.g. "ezidebit", "stripe", "gocardless"
            $table->string('gateway_payment_id')->nullable()->index();   // gateway's transaction / mandate ref
            $table->string('gateway_batch_id')->nullable()->index();     // batch reference if submitted in bulk
            $table->string('our_reference')->nullable();                 // our internal idempotency / reference key

            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('submitted_to_gateway_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Failure details
            $table->string('failure_code')->nullable();     // gateway error code
            $table->text('failure_reason')->nullable();     // human-readable message

            // Retry tracking
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->unsignedBigInteger('retry_of_id')->nullable(); // FK → direct_debit_payments.id

            /*
            |------------------------------------------------------------------
            | Xero write-back
            |------------------------------------------------------------------
            */
            $table->uuid('xero_payment_id')->nullable()->index();       // PaymentID returned by Xero
            $table->uuid('xero_bank_account_id')->nullable();           // AccountID used for the payment in Xero
            $table->timestamp('xero_payment_posted_at')->nullable();    // when we successfully POSTed to Xero
            $table->text('xero_post_error')->nullable();                // Xero API error if write-back failed
            $table->boolean('xero_post_attempted')->default(false);

            $table->decimal('stripe_fee', 10, 2)->nullable();
            $table->decimal('stripe_net', 10, 2)->nullable();
            $table->string('stripe_balance_transaction_id')->nullable();

            $table->string('initiated_by_type', 20)->default('scheduled');
            $table->unsignedBigInteger('initiated_by_user_id')->nullable();


            $table->timestamps();

            /*
            |------------------------------------------------------------------
            | Foreign keys
            |------------------------------------------------------------------
            */
            $table->foreign('xero_invoice_id')
                ->references('id')->on('xero_invoices')
                ->cascadeOnDelete();

            $table->foreign('xero_tenant_id')
                ->references('id')->on('xero_tenants')
                ->cascadeOnDelete();

            $table->foreign('client_id')
                ->references('id')->on('clients')
                ->nullOnDelete();

            $table->foreign('retry_of_id')
                ->references('id')->on('direct_debit_payments')
                ->nullOnDelete();

            $table->foreign('initiated_by_user_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            // Find all pending/processing payments for a tenant
            $table->index(['xero_tenant_id', 'status']);

            // Pending Xero write-backs
            $table->index(['xero_post_attempted', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_debit_payments');
    }
};
