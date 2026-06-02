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
        Schema::table('m_worker_profiles', function (Blueprint $table) {
            $table->date('dob')->nullable()->after('phone');
            $table->string('id_card_number', 20)->nullable()->after('dob');
            $table->date('id_card_issue_date')->nullable()->after('id_card_number');
            $table->string('permanent_address', 500)->nullable()->after('id_card_issue_date');
            $table->unsignedBigInteger('selfie_id')->nullable()->after('permanent_address');
            $table->unsignedBigInteger('id_card_front_id')->nullable()->after('selfie_id');
            $table->unsignedBigInteger('id_card_back_id')->nullable()->after('id_card_front_id');
            $table->string('gender', 10)->nullable()->after('id_card_back_id');

            $table->foreign('selfie_id')->references('id')->on('t_images')->nullOnDelete();
            $table->foreign('id_card_front_id')->references('id')->on('t_images')->nullOnDelete();
            $table->foreign('id_card_back_id')->references('id')->on('t_images')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_worker_profiles', function (Blueprint $table) {
            $table->dropForeign(['selfie_id']);
            $table->dropForeign(['id_card_front_id']);
            $table->dropForeign(['id_card_back_id']);

            $table->dropColumn([
                'dob',
                'id_card_number',
                'id_card_issue_date',
                'permanent_address',
                'selfie_id',
                'id_card_front_id',
                'id_card_back_id',
                'gender',
            ]);
        });
    }
};
