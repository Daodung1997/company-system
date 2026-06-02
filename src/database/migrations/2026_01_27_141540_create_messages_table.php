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
        Schema::create('t_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('t_jobs');
            $table->foreignId('sender_id')->constrained('m_users');
            $table->text('content')->nullable();
            $table->string('type', 20)->default('text'); // text, image, file
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_messages');
    }
};
