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
        Schema::create('m_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('title', 255);
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 15, 2);
            $table->decimal('max_discount_amount', 15, 2)->nullable();
            $table->decimal('min_order_amount', 15, 2)->default(0);
            $table->integer('total_quantity')->nullable();
            $table->integer('used_quantity')->default(0);
            $table->integer('max_uses_per_user')->default(1);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('status', 20)->default('ACTIVE');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index('code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_discounts');
    }
};
