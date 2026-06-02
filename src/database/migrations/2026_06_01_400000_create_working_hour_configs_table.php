<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hour_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time'); // e.g. '08:30:00'
            $table->time('end_time');   // e.g. '17:30:00'
            $table->boolean('is_default')->default(false);
            $table->tinyInteger('saturday_mode')->default(0); // 0 = Off, 1 = Saturday Morning, 2 = Saturday Full Day
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hour_configs');
    }
};
