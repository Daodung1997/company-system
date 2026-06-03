<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('working_hour_configs', function (Blueprint $table) {
            $table->boolean('allow_overtime')->default(false)->after('is_default');
            $table->decimal('max_overtime_hours', 4, 2)->nullable()->after('allow_overtime');
        });
    }

    public function down(): void
    {
        Schema::table('working_hour_configs', function (Blueprint $table) {
            $table->dropColumn(['allow_overtime', 'max_overtime_hours']);
        });
    }
};
