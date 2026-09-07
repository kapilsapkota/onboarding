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
        Schema::table('clients', function ($table) {
            $table->unsignedBigInteger('migration_source_id')
                ->nullable()
                ->unique();
        });

        Schema::table('client_contacts', function ($table) {
            $table->unsignedBigInteger('migration_source_id')
                ->nullable();

            $table->index('migration_source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function ($table) {
            $table->dropColumn('migration_source_id');
        });
        Schema::table('client_contacts', function ($table) {
            $table->dropColumn('migration_source_id');
        });
    }
};
