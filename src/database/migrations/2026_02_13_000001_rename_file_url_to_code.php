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
        // t_complaint_evidence
        Schema::table('t_complaint_evidence', function (Blueprint $table) {
            $table->renameColumn('file_url', 'file_code');
        });
        Schema::table('t_complaint_evidence', function (Blueprint $table) {
            $table->index('file_code');
        });

        // m_worker_documents
        Schema::table('m_worker_documents', function (Blueprint $table) {
            $table->renameColumn('file_url', 'file_code');
        });
        Schema::table('m_worker_documents', function (Blueprint $table) {
            $table->index('file_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_complaint_evidence', function (Blueprint $table) {
            $table->dropIndex(['file_code']);
            $table->renameColumn('file_code', 'file_url');
        });

        Schema::table('m_worker_documents', function (Blueprint $table) {
            $table->dropIndex(['file_code']);
            $table->renameColumn('file_code', 'file_url');
        });
    }
};
