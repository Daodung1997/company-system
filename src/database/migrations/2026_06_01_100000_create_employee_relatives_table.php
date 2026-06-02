<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_relatives', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->nullable();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('relationship', 50); // SPOUSE, CHILD, PARENT, SIBLING, OTHER
            $table->string('full_name', 150);
            $table->string('full_name_kana', 150)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable(); // MALE, FEMALE, OTHER
            $table->string('phone', 20); // Required - emergency contact
            $table->string('email', 150)->nullable();
            $table->string('identity_number', 50)->nullable();
            $table->string('occupation', 150)->nullable();
            $table->string('address', 500)->nullable();
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('is_dependent')->default(false);
            $table->text('notes')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'relationship']);
            $table->index(['employee_id', 'is_emergency_contact']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_relatives');
    }
};
