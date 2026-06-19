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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20); // xero | stripe
            $table->string('event_id')->nullable()->unique();
            $table->string('event_type');
            $table->json('payload');
            $table->enum('status', ['pending', 'processing', 'processed', 'failed', 'skipped'])
                ->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('processing_log')->nullable();
            $table->timestamps();

            $table->index(['source', 'status']);
            $table->index(['event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
