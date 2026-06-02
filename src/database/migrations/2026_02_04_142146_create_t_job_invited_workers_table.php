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
        Schema::create('t_job_invited_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('t_jobs')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('m_users')->onDelete('cascade');
            $table->string('status')->default('assigned'); // assigned, rejected
            $table->timestamps();

            $table->unique(['job_id', 'worker_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_job_invited_workers');
    }
};
