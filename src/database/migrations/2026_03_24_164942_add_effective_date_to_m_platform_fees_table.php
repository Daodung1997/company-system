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
        Schema::table('m_platform_fees', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dateTime('effective_date')->useCurrent()->after('amount');
            $table->unique(['code', 'effective_date'], 'm_platform_fees_code_effective_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_platform_fees', function (Blueprint $table) {
            $table->dropUnique('m_platform_fees_code_effective_date_unique');
            $table->dropColumn('effective_date');
            $table->unique('code');
        });
    }
};
