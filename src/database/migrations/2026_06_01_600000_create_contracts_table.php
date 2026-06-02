<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('restrict');
            $table->string('contract_code', 100)->unique();
            $table->string('type', 50); // LABOR, VENDOR, CLIENT
            $table->string('employment_type', 50)->nullable(); // SEISHAIN, KEIYAKUSHAIN, HAKEN, ARUBAITO, FULL_TIME_VN, PART_TIME_VN
            
            // Advanced Labor Contract Fields
            $table->string('job_title', 100)->nullable(); 
            $table->string('work_location', 255)->nullable(); 
            $table->decimal('working_hours_per_day', 4, 2)->default(8.00); 
            $table->integer('probation_salary_percentage')->default(85); 
            $table->string('bank_name', 100)->nullable(); 
            $table->string('bank_account_number', 50)->nullable(); 

            // Advanced Commercial/Partner/Vendor Contract Fields
            $table->string('partner_name', 255)->nullable();
            $table->string('partner_tax_code', 50)->nullable();
            $table->string('partner_representative', 100)->nullable();
            $table->string('partner_representative_role', 100)->nullable(); 
            $table->string('partner_address', 255)->nullable();
            $table->string('payment_method', 50)->nullable(); 
            $table->string('payment_terms', 100)->nullable(); 
            $table->string('billing_cycle', 50)->nullable(); 

            $table->boolean('is_36_agreement_applicable')->default(false);
            $table->boolean('overtime_allowance_included')->default(false);
            $table->integer('included_overtime_hours')->default(0);
            $table->integer('probation_period_months')->default(0);
            $table->string('insurance_enrolled', 255)->nullable();
            $table->date('sign_date');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('value', 15, 2)->default(0.00);
            $table->string('status', 50)->default('ACTIVE'); // ACTIVE, EXPIRED, TERMINATED, PENDING
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('employee_id');
            $table->index(['end_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
