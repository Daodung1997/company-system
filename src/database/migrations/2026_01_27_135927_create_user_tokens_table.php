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
        Schema::create('m_user_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('m_users');
            $table->string('token')->unique();
            $table->string('device_id')->nullable();
            $table->timestamp('expires_at')->nullable();
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
        Schema::dropIfExists('m_user_tokens');
    }
};
