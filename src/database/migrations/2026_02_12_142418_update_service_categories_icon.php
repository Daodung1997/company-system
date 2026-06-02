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
        Schema::table('m_service_categories', function (Blueprint $table) {
            $table->renameColumn('icon_url', 'icon_code');
            $table->index('icon_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_service_categories', function (Blueprint $table) {
            $table->dropIndex(['icon_code']);
            $table->renameColumn('icon_code', 'icon_url');
        });
    }
};
