<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('year_month', 7); // e.g. '2026-06'
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('standard_working_days', 8, 2)->default(0);
            $table->decimal('actual_working_days', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_salary', 15, 2)->default(0);
            $table->decimal('allowance_attendance', 15, 2)->default(0);
            $table->decimal('deduction_late', 15, 2)->default(0);
            $table->decimal('deduction_leave', 15, 2)->default(0);
            $table->decimal('advance_payment', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->string('status', 20)->default('PENDING'); // PENDING, PAID
            $table->string('note', 500)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['employee_id', 'year_month']);
            $table->index('year_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
