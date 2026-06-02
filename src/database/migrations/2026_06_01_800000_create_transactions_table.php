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
        Schema::create('t_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('type', 20); // EXPENSE, REVENUE
            $table->decimal('amount', 15, 2);
            $table->decimal('net_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->string('tax_rate_type', 20)->default('NONE');
            $table->string('invoice_registration_number', 50)->nullable();
            $table->decimal('withholding_tax', 15, 2)->default(0);
            $table->string('payment_method', 30);
            $table->string('category', 100);
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('PAID');
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();

            $table->index('code');
            $table->index('type');
            $table->index('status');
            $table->index('transaction_date');
        });

        // Add foreign key constraint to t_documents for transaction_id
        Schema::table('t_documents', function (Blueprint $table) {
            $table->foreign('transaction_id')->references('id')->on('t_transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('t_documents')) {
            Schema::table('t_documents', function (Blueprint $table) {
                // Check if foreign key exists first to avoid sqlite errors during test
                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['transaction_id']);
                }
            });
        }

        Schema::dropIfExists('t_transactions');
    }
};
