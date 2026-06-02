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
        Schema::create('t_images', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('origin_name', 255)->nullable();
            $table->string('path_image_original', 500)->nullable();
            $table->string('path_image_resize', 500)->nullable();
            $table->string('disk', 20)->default('public')->nullable();
            $table->string('extension', 10)->nullable();
            $table->bigInteger('filesize')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index('code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_images');
    }
};
