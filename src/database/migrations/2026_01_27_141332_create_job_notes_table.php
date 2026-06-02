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
        Schema::create('t_job_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('t_jobs');
            $table->foreignId('admin_id')->constrained('m_admins');
            $table->text('note');
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
        Schema::dropIfExists('t_job_notes');
    }
};
