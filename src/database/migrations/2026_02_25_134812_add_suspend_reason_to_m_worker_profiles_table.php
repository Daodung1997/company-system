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
        Schema::table('m_worker_profiles', function (Blueprint $table) {
            $table->string('suspend_reason', 1000)->nullable()->after('rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_worker_profiles', function (Blueprint $table) {
            $table->dropColumn('suspend_reason');
        });
    }
};
