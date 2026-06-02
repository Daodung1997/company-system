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
        Schema::table('t_wallet_transactions', function (Blueprint $table) {
            $table->timestamp('release_at')->nullable()->after('status')->comment('Thời điểm tự động giải ngân');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('release_at');
        });
    }
};
