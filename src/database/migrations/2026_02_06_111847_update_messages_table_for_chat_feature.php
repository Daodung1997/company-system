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
        Schema::table('t_messages', function (Blueprint $table) {
            if (Schema::hasColumn('t_messages', 'job_id')) {
                // Drop foreign key first if users have it constrained
                // Note: Index names are usually table_column_foreign, but might differ.
                // Assuming standard naming or ignoring error if not exists in strict mode?
                // Safest to try dropping constraint by array syntax which laravel resolves to name.
                try {
                    $table->dropForeign(['job_id']);
                } catch (\Exception $e) {
                    // constraint might not exist
                }

                try {
                    $table->dropIndex(['job_id']);
                } catch (\Exception $e) {
                    // index might not exist
                }

                $table->dropColumn('job_id');
            }

            if (! Schema::hasColumn('t_messages', 'conversation_id')) {
                $table->foreignId('conversation_id')->after('id')->constrained('t_conversations')->cascadeOnDelete();
            }

            // Add index for fast retrieval
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_messages', function (Blueprint $table) {
            if (Schema::hasColumn('t_messages', 'conversation_id')) {
                $table->dropForeign(['conversation_id']);
                $table->dropColumn('conversation_id');
            }

            if (! Schema::hasColumn('t_messages', 'job_id')) {
                $table->foreignId('job_id')->nullable()->constrained('t_jobs');
            }
        });
    }
};
