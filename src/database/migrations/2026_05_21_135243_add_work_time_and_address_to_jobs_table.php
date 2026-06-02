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
        Schema::table('t_jobs', function (Blueprint $table) {
            $table->string('work_time_type', 20)->nullable()->after('time_slot');
            $table->time('work_start_time')->nullable()->after('work_time_type');
            $table->time('work_end_time')->nullable()->after('work_start_time');
            $table->foreignId('user_address_id')->nullable()->after('area_id')->constrained('m_user_addresses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_jobs', function (Blueprint $table) {
            $table->dropForeign(['user_address_id']);
            $table->dropColumn(['work_time_type', 'work_start_time', 'work_end_time', 'user_address_id']);
        });
    }
};
