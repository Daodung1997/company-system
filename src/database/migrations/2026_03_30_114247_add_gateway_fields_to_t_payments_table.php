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
        Schema::table('t_payments', function (Blueprint $table) {
            $table->string('gateway_provider', 30)->nullable()->after('payment_method');
            $table->string('gateway_order_id', 100)->nullable()->after('gateway_provider');
            $table->json('gateway_request_data')->nullable()->after('gateway_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_payments', function (Blueprint $table) {
            $table->dropColumn(['gateway_provider', 'gateway_order_id', 'gateway_request_data']);
        });
    }
};
