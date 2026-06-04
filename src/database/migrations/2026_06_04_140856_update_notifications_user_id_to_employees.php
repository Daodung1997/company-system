<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_notifications', function (Blueprint $table) {
            // Drop existing foreign key to m_users
            $table->dropForeign(['user_id']);
            
            // Re-add foreign key pointing to employees table
            $table->foreign('user_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('t_notifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('m_users')->onDelete('cascade');
        });
    }
};
