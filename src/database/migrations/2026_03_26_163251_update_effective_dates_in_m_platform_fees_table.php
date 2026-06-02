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
            $table->dropUnique('m_platform_fees_code_effective_date_unique');
            $table->renameColumn('effective_date', 'start_date');
        });

        Schema::table('m_platform_fees', function (Blueprint $table) {
            $table->dateTime('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_platform_fees', function (Blueprint $table) {
            $table->dropColumn('end_date');
            $table->renameColumn('start_date', 'effective_date');
        });

        Schema::table('m_platform_fees', function (Blueprint $table) {
            $table->unique(['code', 'effective_date'], 'm_platform_fees_code_effective_date_unique');
        });
    }
};
