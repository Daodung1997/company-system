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
        Schema::create('t_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('job_id')->constrained('t_jobs');
            $table->foreignId('worker_id')->constrained('m_users');
            $table->decimal('price', 12, 0);
            $table->string('estimated_duration', 50)->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending'); // pending, accepted, rejected
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->unique(['job_id', 'worker_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_quotations');
    }
};
