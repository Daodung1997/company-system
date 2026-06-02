<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->string('timezone', 50)->default('Asia/Ho_Chi_Minh'); // Asia/Ho_Chi_Minh or Asia/Tokyo
            $table->string('status', 20)->default('ABSENT'); // PRESENT, ABSENT, LATE, LEAVE
            $table->text('note')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();

            // Indexes & Unique Constraints
            $table->unique(['employee_id', 'date']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
