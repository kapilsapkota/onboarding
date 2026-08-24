<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_customers', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_customer_id')->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('default_payment_method_id')->nullable();
            $table->json('stripe_data');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_customers');
    }
};
