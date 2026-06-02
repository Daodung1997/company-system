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
        Schema::create('t_payments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('job_id')->unique()->constrained('t_jobs');
            $table->decimal('amount', 12, 0);
            $table->decimal('platform_fee', 12, 0);
            $table->decimal('worker_earning', 12, 0);
            $table->string('payment_method', 30); // bank_transfer, vnpay
            $table->string('status', 20); // pending, released
            $table->string('transaction_reference', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refunded_amount', 12, 0)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index('status');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_payments');
    }
};
