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
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedInteger('quote_sequence')->nullable();
            $table->unsignedInteger('revision_number')->default(0);
            $table->foreignId('parent_quote_id')
                ->nullable()
                ->constrained('quotes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('quote_sequence');
            $table->dropColumn('revision_number');
            $table->dropColumn('parent_quote_id');
        });
    }
};
