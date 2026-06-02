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
        Schema::table('t_complaint_evidence', function (Blueprint $table) {
            $table->foreignId('uploader_id')->nullable()->after('complaint_id')->constrained('m_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_complaint_evidence', function (Blueprint $table) {
            $table->dropForeign(['uploader_id']);
            $table->dropColumn('uploader_id');
        });
    }
};
