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
        Schema::table('employees', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('romaji_name');
            $table->string('gender', 20)->nullable()->after('date_of_birth');
            $table->string('hometown', 255)->nullable()->after('gender');
            $table->string('place_of_birth', 255)->nullable()->after('hometown');
            $table->string('nationality', 100)->nullable()->after('place_of_birth');
            $table->string('ethnicity', 100)->nullable()->after('nationality');
            $table->string('religion', 100)->nullable()->after('ethnicity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'gender',
                'hometown',
                'place_of_birth',
                'nationality',
                'ethnicity',
                'religion',
            ]);
        });
    }
};
