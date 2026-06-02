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
        Schema::create('t_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('customer_id')->constrained('m_users');
            $table->foreignId('service_id')->constrained('m_service_categories');
            $table->foreignId('worker_id')->nullable()->constrained('m_users');
            $table->text('description');
            $table->foreignId('area_id')->constrained('m_areas');
            $table->string('address', 500);
            $table->date('scheduled_date')->nullable();
            $table->string('time_slot', 50)->nullable(); // morning, afternoon, evening
            $table->string('status', 30)->default('waiting_for_quotation');
            $table->decimal('quotation_price', 12, 0)->nullable();
            $table->decimal('platform_fee', 12, 0)->nullable();
            $table->decimal('total_amount', 12, 0)->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index('status');
            $table->index('scheduled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_jobs');
    }
};
