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
        Schema::create('stripe_balance_transactions', function (Blueprint $table) {
    $table->id();

    $table->string('stripe_balance_transaction_id')->unique();
    $table->string('source_id')->nullable()->index();

    $table->string('type')->nullable()->index();

    $table->string('reporting_category')->nullable()->index();

    $table->string('currency', 3)->default('aud');
    $table->bigInteger('amount');

    $table->unsignedBigInteger('fee')->default(0);

    $table->bigInteger('net');
    $table->timestamp('available_at')->nullable()->index();
    $table->timestamp('occurred_at')->nullable()->index();
    $table->foreignId('stripe_payout_id')
        ->nullable()
        ->constrained('stripe_payouts')
        ->nullOnDelete();
    $table->json('stripe_data')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamps();
    $table->index(['source_id', 'type']);
    $table->index(['stripe_payout_id', 'occurred_at']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_balance_transactions');
    }
};
