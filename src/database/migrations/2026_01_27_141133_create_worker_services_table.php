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
        Schema::create('m_worker_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_profile_id')->constrained('m_worker_profiles');
            $table->foreignId('service_category_id')->constrained('m_service_categories');
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->unique(['worker_profile_id', 'service_category_id'], 'worker_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_worker_services');
    }
};
