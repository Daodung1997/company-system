<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('deduction_union', 15, 2)->default(0)->after('deduction_leave');
            $table->decimal('deduction_tax', 15, 2)->default(0)->after('deduction_union');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['deduction_union', 'deduction_tax']);
        });
    }
};
