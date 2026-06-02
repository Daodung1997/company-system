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
        Schema::create('t_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('worker_id')->constrained('m_users');
            $table->foreignId('job_id')->nullable()->constrained('t_jobs');
            $table->string('type', 20); // earning, withdrawal
            $table->decimal('amount', 12, 0);
            $table->string('status', 20); // pending, released
            $table->text('description')->nullable();
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index('worker_id');
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_wallet_transactions');
    }
};
