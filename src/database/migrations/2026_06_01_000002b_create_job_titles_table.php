<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->nullable(); // Support for BaseMasterModel prefix code
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();

            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};
