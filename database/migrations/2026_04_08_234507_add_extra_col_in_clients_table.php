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
            $table->string('company_phone')->nullable();
            $table->string('address_second')->nullable();
            $table->string('state')->nullable();
            $table->text('service_providers')->nullable();
            $table->text('services')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bsb')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('company_phone');
            $table->dropColumn('address_second');
            $table->dropColumn('state');
            $table->dropColumn('service_providers');
        });
    }
};
