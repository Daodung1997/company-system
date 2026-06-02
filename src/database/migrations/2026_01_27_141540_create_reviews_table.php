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
        Schema::create('t_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('t_jobs');
            $table->foreignId('reviewer_id')->constrained('m_users');
            $table->foreignId('target_id')->constrained('m_users'); // Worker or Customer reviewed
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_reviews');
    }
};
