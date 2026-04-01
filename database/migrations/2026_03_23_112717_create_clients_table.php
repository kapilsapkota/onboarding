<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('industry')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('post_code')->nullable();
            $table->string('abn')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('monthly_budget')->nullable();
            $table->string('referred_by')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('twitter')->nullable();
            $table->string('whatsapp_group')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('contacts_file_path')->nullable();
            $table->text('website')->nullable();
            $table->text('notes')->nullable();
            $table->text('pasted_employees')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('full_name')->nullable();
            $table->string('role')->nullable();
            $table->string('contact_type')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->date('birthday')->nullable();
            $table->boolean('email_opt_in')->default(false);
            $table->boolean('sms_opt_in')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
        Schema::dropIfExists('clients');
    }
};
