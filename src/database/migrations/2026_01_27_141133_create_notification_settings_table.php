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
        Schema::create('m_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('m_users');
            $table->string('type', 50); // promotion, systems, job_update
            $table->string('channel', 20); // push, email
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->unique(['user_id', 'type', 'channel'], 'notif_setting_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_notification_settings');
    }
};
