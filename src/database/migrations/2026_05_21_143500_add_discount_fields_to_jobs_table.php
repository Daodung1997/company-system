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
        Schema::table('t_jobs', function (Blueprint $table) {
            $table->foreignId('discount_id')->nullable()->after('total_amount')->constrained('m_discounts')->onDelete('set null');
            $table->string('discount_code', 50)->nullable()->after('discount_id');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_code');
            $table->decimal('original_amount', 15, 2)->nullable()->after('discount_amount');
            $table->decimal('final_amount', 15, 2)->nullable()->after('original_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_jobs', function (Blueprint $table) {
            $table->dropForeign(['discount_id']);
            $table->dropColumn([
                'discount_id',
                'discount_code',
                'discount_amount',
                'original_amount',
                'final_amount',
            ]);
        });
    }
};
