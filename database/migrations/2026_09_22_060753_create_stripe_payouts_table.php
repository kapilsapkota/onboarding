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
        Schema::create('stripe_payouts', function (Blueprint $table) {
    $table->id();

    $table->string('stripe_payout_id')->unique();

    $table->string('status')->nullable()->index();

    $table->string('type')->nullable();

    $table->string('method')->nullable();

    $table->string('currency', 3)->default('aud');
    $table->unsignedBigInteger('amount');
    $table->timestamp('arrival_at')->nullable()->index();
    $table->timestamp('paid_at')->nullable();
    $table->string('destination')->nullable();
    $table->string('description')->nullable();
    $table->json('stripe_data')->nullable();

    $table->timestamp('last_synced_at')->nullable();

    $table->timestamps();

    $table->index(['currency', 'status']);
    $table->index('arrival_at');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_payouts');
    }
};
