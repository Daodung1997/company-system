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
        Schema::create('m_worker_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('m_users');
            $table->string('phone', 20)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->tinyInteger('experience_years')->unsigned()->nullable();
            $table->text('skill_description')->nullable();
            $table->string('profile_status', 20)->default('incomplete'); // incomplete, pending_approval, approved, rejected
            $table->string('activity_status', 20)->default('inactive');   // inactive, active, suspended
            $table->boolean('availability')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index('profile_status');
            $table->index('activity_status');
            $table->index('availability');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('m_worker_profiles');
    }
};
