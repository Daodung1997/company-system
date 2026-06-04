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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('company_name');
            $table->string('company_name_kana')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('corporate_number')->nullable();
            $table->string('address_registered')->nullable();
            $table->string('legal_representative')->nullable();
            $table->string('representative_title')->nullable();
            $table->string('representative_id_number')->nullable();
            $table->date('representative_id_date')->nullable();
            $table->string('representative_id_place')->nullable();
            $table->string('charter_capital')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('fax')->nullable();
            $table->string('postcode')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->string('hanko_seal_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('background_path')->nullable();
            $table->string('sidebar_name')->nullable();
            $table->string('slogan_1')->nullable();
            $table->string('slogan_2')->nullable();
            $table->string('slogan_3')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
