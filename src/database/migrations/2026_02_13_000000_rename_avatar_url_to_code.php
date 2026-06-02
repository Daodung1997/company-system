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
        $tables = ['m_customer_profiles', 'm_worker_profiles', 'm_admins'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('avatar_url', 'avatar_code');
            });

            Schema::table($tableName, function (Blueprint $table) {
                // Determine length based on previous definition or default string length
                // Since renameColumn preserves type, we just add index.
                // However, MySQL needs length for index if varchar is too long, but 255/500 is fine.
                // Previous definition was string('avatar_url', 500).
                $table->index('avatar_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['m_customer_profiles', 'm_worker_profiles', 'm_admins'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['avatar_code']);
                $table->renameColumn('avatar_code', 'avatar_url');
            });
        }
    }
};
