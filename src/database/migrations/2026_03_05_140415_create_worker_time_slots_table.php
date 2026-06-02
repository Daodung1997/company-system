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
        Schema::create('m_worker_time_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('worker_profile_id');
            $table->string('time_slot', 20); // e.g. '08:00-10:00'
            $table->timestamps();

            $table->foreign('worker_profile_id')
                ->references('id')
                ->on('m_worker_profiles')
                ->onDelete('cascade');

            $table->unique(['worker_profile_id', 'time_slot'], 'uq_worker_time_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_worker_time_slots');
    }
};
