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
        Schema::create('t_complaints', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('job_id')->unique()->constrained('t_jobs');
            $table->text('description');
            $table->string('status', 20)->default('pending'); // pending, processing, resolved
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolver_id')->nullable()->constrained('m_admins');
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_complaints');
    }
};
