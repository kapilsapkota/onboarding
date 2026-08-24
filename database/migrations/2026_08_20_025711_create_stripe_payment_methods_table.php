<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stripe_customer_id')
                ->constrained('stripe_customers')
                ->cascadeOnDelete();
            $table->string('stripe_payment_method_id')->unique();
            $table->string('type');
            $table->string('last4')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->json('stripe_data');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_payment_methods');
    }
};
