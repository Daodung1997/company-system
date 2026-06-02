<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->nullable(); // Support for BaseMasterModel prefix code
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->onDelete('restrict');
            $table->string('full_name', 150);
            $table->string('full_name_kana', 150)->nullable(); // JP Katakana
            $table->string('romaji_name', 150)->nullable(); // JP Romaji
            $table->string('email', 150)->unique(); // Required
            $table->string('phone', 20); // Required (emergency & direct)
            $table->string('password', 255); // Required for Authentication
            
            // Personal identifiers
            $table->string('identity_type', 50)->default('CCCD'); // CCCD, MY_NUMBER, ZAIRYU_CARD, PASSPORT
            $table->string('identity_number', 50)->unique()->nullable();
            $table->date('zairyu_card_expiry')->nullable();
            
            // Tax & Insurance
            $table->string('tax_code', 50)->nullable();
            $table->string('social_insurance_code', 50)->nullable();
            $table->string('pension_number', 50)->nullable();
            $table->string('employment_insurance_number', 50)->nullable();
            
            // Banking details
            $table->string('bank_code', 10)->nullable();
            $table->string('bank_branch_code', 10)->nullable();
            $table->string('bank_account_type', 50)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_holder_kana', 150)->nullable();
            
            // Custom role parameter (e.g. ADMIN, MANAGER, STAFF)
            $table->string('role', 50)->default('STAFF');
            
            // Additional details
            $table->integer('dependents_count')->default(0);
            $table->string('address_registered', 500)->nullable();
            $table->string('address_current', 500)->nullable();
            
            $table->string('status', 50)->default('PROBATION'); // ACTIVE, INACTIVE, PROBATION
            $table->date('join_date');
            $table->boolean('must_change_password')->default(true);
            $table->mediumText('avatar')->nullable();
            
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['department_id', 'status']);
            $table->index(['job_title_id', 'status']);
            $table->index('identity_number');
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
