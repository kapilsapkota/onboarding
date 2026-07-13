<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->text('description')->nullable();
            $table->text('scope_items')->nullable();
            $table->string('key_scope_keyword')->nullable();

            $table->enum('price_type', ['fixed', 'dropdown'])->default('fixed');
            $table->decimal('fixed_price', 10, 2)->nullable();
            $table->decimal('price_min', 10, 2)->nullable();
            $table->decimal('price_max', 10, 2)->nullable();
            $table->decimal('price_increment', 10, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();

            $table->string('frequency')->default('once_off');

            $table->string('image_url')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
