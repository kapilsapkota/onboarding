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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('setup_fee', 10,2)->after('hourly_rate')->nullable();
            $table->boolean('quote_default')->default(false);
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->decimal('setup_fee', 10,2)->after('unit_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('setup_fee');
            $table->dropColumn('quote_default');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->decimal('setup_fee', 10,2)->after('unit_price')->nullable();
        });
    }
};
