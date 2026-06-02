<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_worker_registration_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('worker_profile_id');
            $table->integer('attempt_number')->default(1);

            // Snapshot of submitted data
            $table->string('phone')->nullable();
            $table->date('dob')->nullable();
            $table->string('id_card_number')->nullable();
            $table->date('id_card_issue_date')->nullable();
            $table->text('permanent_address')->nullable();
            $table->unsignedBigInteger('selfie_id')->nullable();
            $table->unsignedBigInteger('id_card_front_id')->nullable();
            $table->unsignedBigInteger('id_card_back_id')->nullable();
            $table->string('gender')->nullable();
            $table->integer('experience_years')->nullable();
            $table->text('skill_description')->nullable();
            $table->json('service_ids')->nullable();
            $table->json('area_ids')->nullable();

            // Action tracking
            $table->string('action'); // submitted, approved, rejected
            $table->unsignedBigInteger('action_by')->nullable(); // Admin user ID
            $table->text('action_reason')->nullable(); // Rejection reason

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('worker_profile_id')
                ->references('id')
                ->on('m_worker_profiles')
                ->onDelete('cascade');

            $table->foreign('selfie_id')
                ->references('id')
                ->on('t_images')
                ->onDelete('set null');

            $table->foreign('id_card_front_id')
                ->references('id')
                ->on('t_images')
                ->onDelete('set null');

            $table->foreign('id_card_back_id')
                ->references('id')
                ->on('t_images')
                ->onDelete('set null');

            $table->foreign('action_by')
                ->references('id')
                ->on('m_admins')
                ->onDelete('set null');

            $table->index(['worker_profile_id', 'created_at'], 'wrh_profile_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_worker_registration_histories');
    }
};
