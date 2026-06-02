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
            $table->decimal('latitude', 10, 7)->nullable()->after('availability');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['latitude', 'longitude'], 'idx_worker_geo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_worker_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_worker_geo');
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
