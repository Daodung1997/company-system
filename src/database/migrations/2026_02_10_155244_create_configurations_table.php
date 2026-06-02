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
        Schema::create('m_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Configuration key');
            $table->text('value')->comment('Configuration value');
            $table->string('description', 255)->nullable()->comment('Description of the configuration');

            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_configurations');
    }
};
