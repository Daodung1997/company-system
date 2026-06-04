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
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('restrict');
            $table->string('company_name', 255)->nullable()->after('employee_id');
            $table->string('company_tax_code', 50)->nullable()->after('company_name');
            $table->string('company_address', 255)->nullable()->after('company_tax_code');
            $table->string('company_representative', 100)->nullable()->after('company_address');
            $table->string('company_representative_role', 100)->nullable()->after('company_representative');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'company_id',
                'company_name',
                'company_tax_code',
                'company_address',
                'company_representative',
                'company_representative_role'
            ]);
        });
    }
};
