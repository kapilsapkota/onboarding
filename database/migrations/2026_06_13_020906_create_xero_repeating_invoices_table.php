<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xero_repeating_invoices', function (Blueprint $table) {
            $table->id();

            // Xero identifiers
            $table->unsignedBigInteger('xero_tenant_id')->index();
            $table->uuid('xero_repeating_invoice_id')->unique();
            $table->string('type', 10);
            $table->string('status', 20)->index();

            // Contact
            $table->unsignedBigInteger('xero_contact_id')->nullable()->index();   // local FK
            $table->string('xero_contact_xero_id')->nullable()->index();            // raw Xero ContactID

            // Schedule
            $table->unsignedSmallInteger('schedule_period')->nullable();          // e.g. 1, 2
            $table->string('schedule_period_type')->nullable();                   // WEEKLY | MONTHLY | YEARLY | DAILY
            $table->unsignedSmallInteger('schedule_due_date')->nullable();        // numeric due date offset
            $table->string('schedule_due_date_type')->nullable();                 // DAYSAFTERBILLDATE | OFFOLLOWINGMONTH | etc.
            $table->date('schedule_start_date')->nullable();
            $table->date('schedule_next_scheduled_date')->nullable()->index();
            $table->date('schedule_end_date')->nullable();

            $table->char('currency_code', 3)->nullable();
            $table->decimal('sub_total', 15, 2)->nullable();
            $table->decimal('total_tax', 15, 2)->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->json('line_items')->nullable();

            // Reference
            $table->string('reference')->nullable();
            $table->uuid('xero_branding_theme_id')->nullable();
            $table->boolean('has_attachments')->default(false);

            // Sync tracking
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('xero_tenant_id')
                ->references('id')->on('xero_tenants')
                ->cascadeOnDelete();

            $table->foreign('xero_contact_id')
                ->references('id')->on('xero_contacts')
                ->nullOnDelete();
        });

//        Schema::table('xero_invoices', function (Blueprint $table) {
//            $table->unsignedBigInteger('xero_repeating_invoice_id')
//                ->nullable()
//                ->index()
//                ->after('xero_branding_theme_id');
//
//            $table->foreign('xero_repeating_invoice_id')
//                ->references('id')->on('xero_repeating_invoices')
//                ->nullOnDelete();
//        });
    }

    public function down(): void
    {
        Schema::table('xero_invoices', function (Blueprint $table) {
            $table->dropForeign(['xero_repeating_invoice_id']);
            $table->dropColumn('xero_repeating_invoice_id');
        });

        Schema::dropIfExists('xero_repeating_invoices');
    }
};
