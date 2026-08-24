<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_charge_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('stripe_charge_batches')
                ->cascadeOnDelete();
            $table->foreignId('stripe_customer_id')
                ->constrained('stripe_customers')
                ->cascadeOnDelete();
            $table->foreignId('stripe_payment_method_id')
                ->constrained('stripe_payment_methods')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('currency')->default('aud');
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('status')->default('pending');
            $table->json('stripe_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_charge_batch_items');
    }
};
