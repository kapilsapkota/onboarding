<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_delivery_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_delivery_id')
                ->constrained('quote_deliveries')
                ->cascadeOnDelete();

            $table->enum('type', [
                'generate_pdf',
                'generate_public_url',
                'sharepoint_upload',
                'email',
                'sms',
            ]);

            $table->enum('status', [
                'pending',
                'processing',
                'succeeded',
                'failed',
                'skipped',
            ])->default('pending');

            $table->unsignedSmallInteger('attempt_number')->default(1);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Explicit short names to stay under MySQL's 64-char index name limit.
            $table->index(['quote_delivery_id', 'type', 'status'],         'qda_delivery_type_status');
            $table->index(['quote_delivery_id', 'type', 'attempt_number'], 'qda_delivery_type_attempt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_delivery_attempts');
    }
};
