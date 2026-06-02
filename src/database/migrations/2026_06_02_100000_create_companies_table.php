<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_kana')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('corporate_number')->nullable();
            $table->string('address_registered')->nullable();
            $table->string('legal_representative')->nullable();
            $table->string('hanko_seal_path')->nullable();
            $table->string('fax')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('postcode')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
