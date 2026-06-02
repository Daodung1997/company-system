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
            $table->unsignedBigInteger('parent_id')->nullable()->after('sort_order');
            $table->tinyInteger('level')->default(1)->after('parent_id');

            $table->foreign('parent_id')
                ->references('id')
                ->on('m_service_categories')
                ->onDelete('restrict');

            $table->index('parent_id');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_service_categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['level']);
            $table->dropColumn(['parent_id', 'level']);
        });
    }
};
