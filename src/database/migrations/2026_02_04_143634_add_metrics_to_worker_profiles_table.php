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
            $table->decimal('avg_rating', 3, 2)->default(0)->after('availability');
            $table->unsignedInteger('total_completed_jobs')->default(0)->after('avg_rating');
            $table->unsignedInteger('total_cancelled_jobs')->default(0)->after('total_completed_jobs');

            $table->index('avg_rating');
            $table->index('total_completed_jobs');
        });
    }

    public function down(): void
    {
        Schema::table('m_worker_profiles', function (Blueprint $table) {
            $table->dropIndex(['avg_rating']);
            $table->dropIndex(['total_completed_jobs']);

            $table->dropColumn(['avg_rating', 'total_completed_jobs', 'total_cancelled_jobs']);
        });
    }
};
