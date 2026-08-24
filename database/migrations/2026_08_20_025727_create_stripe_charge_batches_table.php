<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_charge_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->unsignedInteger('customer_count')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('currency')->default('aud');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_charge_batches');
    }
};
