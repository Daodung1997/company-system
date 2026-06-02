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
        Schema::create('t_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('origin_name', 255)->nullable();
            $table->string('file_path', 500);
            $table->string('disk', 20)->default('public')->nullable();
            $table->string('extension', 10)->nullable();
            $table->bigInteger('filesize')->default(0);
            $table->unsignedBigInteger('documentable_id')->nullable();
            $table->string('documentable_type', 255)->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');

            $table->index('code');
            $table->index('status');
            $table->index(['documentable_id', 'documentable_type']);
            $table->index('employee_id');
            $table->index('contract_id');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_documents');
    }
};
