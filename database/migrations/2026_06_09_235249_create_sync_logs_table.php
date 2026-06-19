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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20); // xero | local
            $table->string('direction', 20); // push | pull | both
            $table->string('entity_type'); // invoice | payment | contact
            $table->string('entity_id')->nullable();
            $table->string('xero_id')->nullable();
            $table->enum('status', ['success', 'failed', 'skipped', 'conflict'])->default('success');
            $table->json('changes')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['status']);
            $table->index(['created_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
