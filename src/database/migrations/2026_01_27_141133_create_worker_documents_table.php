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
        Schema::create('m_worker_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('worker_profile_id')->nullable();
            $table->foreign('worker_profile_id')->references('id')->on('m_worker_profiles')->onDelete('cascade');
            $table->string('type', 50); // id_card_front, id_card_back, license
            $table->string('file_url', 500);
            $table->string('status', 20)->default('pending');
            $table->timestamp('verified_at')->nullable();
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
        Schema::dropIfExists('m_worker_documents');
    }
};
