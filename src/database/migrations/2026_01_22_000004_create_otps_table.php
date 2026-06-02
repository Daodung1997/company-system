<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_otps', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 100)->comment('Email or Phone');
            $table->string('code', 10);
            $table->string('type', 30)->comment('register, forgot_password, verify_phone, verify_email');
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['identifier', 'type']);
            $table->index(['code', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_otps');
    }
};
