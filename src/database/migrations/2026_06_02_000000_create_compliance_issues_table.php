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
        Schema::create('t_compliance_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            
            $table->string('issue_type', 30); // VISA_EXPIRATION, CONTRACT_EXPIRATION, MISSING_INVOICE, OVERTIME_LIMIT
            $table->string('severity', 20); // CRITICAL, WARNING, INFO
            $table->text('description');
            $table->string('status', 20)->default('OPEN'); // OPEN, RESOLVED, IGNORED
            
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by', 50)->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('set null');
            $table->foreign('transaction_id')->references('id')->on('t_transactions')->onDelete('set null');

            $table->index('issue_type');
            $table->index('severity');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_compliance_issues');
    }
};
