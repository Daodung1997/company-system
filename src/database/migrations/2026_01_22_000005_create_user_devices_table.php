<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('m_users')->onDelete('cascade');
            $table->string('device_id', 100);
            $table->string('device_name', 100);
            $table->string('device_type', 30)->nullable()->comment('ios, android, web');
            $table->string('fcm_token', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'device_id']);
            $table->index('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_user_devices');
    }
};
