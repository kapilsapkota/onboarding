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
        Schema::create('quote_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('authorised_person');
            $table->string('position')->nullable();
            $table->string('signature_path'); // Path to the saved image file
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_signatures');
    }
};
