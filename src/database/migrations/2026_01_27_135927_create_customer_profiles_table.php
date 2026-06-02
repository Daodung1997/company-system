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
        Schema::create('m_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('m_users');
            $table->string('phone', 20)->nullable();
            $table->date('birthday')->nullable();
            $table->tinyInteger('gender')->nullable(); // 1=Male, 2=Female, 3=Other
            $table->string('address', 500)->nullable();
            $table->foreignId('area_id')->nullable()->constrained('m_areas');
            $table->string('avatar_url', 500)->nullable();
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
        Schema::dropIfExists('m_customer_profiles');
    }
};
