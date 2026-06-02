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
        Schema::create('t_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('DIRECT'); // JOB, DIRECT, SUPPORT
            $table->unsignedBigInteger('related_id')->nullable()->index(); // e.g., job_id
            $table->foreignId('creator_id')->constrained('m_users');
            $table->string('status')->default('ACTIVE');
            $table->timestamp('last_message_at')->nullable();
            $table->text('last_message_content')->nullable();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'related_id']);
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_conversations');
    }
};
