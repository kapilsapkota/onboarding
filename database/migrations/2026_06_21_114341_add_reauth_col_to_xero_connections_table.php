<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xero_connections', function (Blueprint $table) {
            $table->boolean('needs_reauth')->default(false)->after('is_active');
            $table->string('reauth_reason')->nullable()->after('needs_reauth');
        });
    }

    public function down(): void
    {
        Schema::table('xero_connections', function (Blueprint $table) {
            $table->dropColumn(['needs_reauth', 'reauth_reason']);
        });
    }
};
