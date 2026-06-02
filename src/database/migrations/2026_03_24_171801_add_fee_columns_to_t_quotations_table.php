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
        Schema::table('t_quotations', function (Blueprint $table) {
            $table->decimal('platform_fee', 12, 0)->default(0)->after('price');
            $table->decimal('total_amount', 12, 0)->default(0)->after('platform_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_quotations', function (Blueprint $table) {
            $table->dropColumn(['platform_fee', 'total_amount']);
        });
    }
};
