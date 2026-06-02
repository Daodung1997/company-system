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
        Schema::create('m_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('m_users');
            $table->string('bank_name', 100);
            $table->string('account_number', 50);
            $table->string('account_name', 100);
            $table->string('branch', 100)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_bank_accounts');
    }
};
