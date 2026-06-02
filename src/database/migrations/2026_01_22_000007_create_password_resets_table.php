<?php

use App\Models\PasswordReset;
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
        Schema::create(PasswordReset::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('token');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PasswordReset::TABLE_NAME);
    }
};
