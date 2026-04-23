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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('status');
            $table->string('stripe_payment_method_id')->nullable()->after('stripe_customer_id');
            $table->string('mandate_id')->nullable()->after('stripe_payment_method_id');
            $table->string('mandate_status')->default('pending')->after('mandate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            //
        });
    }
};
