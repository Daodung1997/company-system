<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('overtime_hours_normal', 8, 2)->default(0)->after('overtime_salary');
            $table->decimal('overtime_salary_normal', 15, 2)->default(0)->after('overtime_hours_normal');
            
            $table->decimal('overtime_hours_weekend', 8, 2)->default(0)->after('overtime_salary_normal');
            $table->decimal('overtime_salary_weekend', 15, 2)->default(0)->after('overtime_hours_weekend');
            
            $table->decimal('overtime_hours_holiday', 8, 2)->default(0)->after('overtime_salary_weekend');
            $table->decimal('overtime_salary_holiday', 15, 2)->default(0)->after('overtime_hours_holiday');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn([
                'overtime_hours_normal',
                'overtime_salary_normal',
                'overtime_hours_weekend',
                'overtime_salary_weekend',
                'overtime_hours_holiday',
                'overtime_salary_holiday'
            ]);
        });
    }
};
