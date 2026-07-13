<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('category_name');
            $table->string('product_name');
            $table->text('scope_of_works')->nullable();
            $table->string('key_scope_keyword')->nullable();

            $table->decimal('unit_price', 10, 2);
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);

            $table->decimal('hours', 8, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();

            $table->string('frequency')->default('once_off');
            $table->string('image_url')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
