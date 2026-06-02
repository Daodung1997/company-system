<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('m_users');
            $table->string('label', 50);
            $table->string('receiver_name', 100);
            $table->string('receiver_phone', 20);
            $table->foreignId('area_id')->constrained('m_areas');
            $table->foreignId('ward_id')->nullable()->constrained('m_areas');
            $table->string('address_detail', 500);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index('user_id');
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_user_addresses');
    }
};
