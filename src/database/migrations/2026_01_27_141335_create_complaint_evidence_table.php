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
        Schema::create('t_complaint_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('t_complaints');
            $table->string('file_url', 500);
            $table->string('type', 20); // image, video
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
        Schema::dropIfExists('t_complaint_evidence');
    }
};
