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
        Schema::create('quote_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')
                ->constrained('quotes')
                ->cascadeOnDelete();

            $table->foreignId('requested_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'partially_failed',
                'failed',
                'cancelled',
            ])->default('pending')->index();

            // ---------------------------------------------------------------
            // What was requested
            // ---------------------------------------------------------------
            $table->boolean('send_email')->default(false);
            $table->boolean('send_sms')->default(false);
            $table->string('email_address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email_subject')->nullable();

            $table->text('email_message')->nullable();
            $table->text('sms_message')->nullable();

            $table->string('public_token', 64)->nullable()->unique();

            $table->string('public_url', 2048)->nullable();

            $table->string('pdf_disk')->nullable()->default('local');
            $table->string('pdf_path')->nullable();
            $table->string('pdf_filename')->nullable();
            $table->unsignedBigInteger('pdf_size')->nullable(); // bytes

            $table->string('sharepoint_file_id')->nullable();
            $table->string('sharepoint_url', 2048)->nullable();

            // ---------------------------------------------------------------
            // Lifecycle timestamps
            // ---------------------------------------------------------------
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            // ---------------------------------------------------------------
            // Indexes
            // ---------------------------------------------------------------
            $table->index(['quote_id', 'status']);
            $table->index(['quote_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_deliveries');
    }
};
