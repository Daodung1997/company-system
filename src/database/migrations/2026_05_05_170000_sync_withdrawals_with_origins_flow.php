<?php

use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_withdrawals', function (Blueprint $table) {
            $table->text('failure_reason')->nullable()->after('rejection_reason');
            $table->string('gateway_reference', 100)->nullable()->after('failure_reason');
            $table->json('gateway_response')->nullable()->after('gateway_reference');
        });

        Schema::table('t_wallet_transactions', function (Blueprint $table) {
            $table->foreignId('withdrawal_id')->nullable()->after('job_id')->constrained('t_withdrawals');
            $table->index('withdrawal_id');
        });

        Schema::create('t_withdrawal_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withdrawal_id')->constrained('t_withdrawals')->cascadeOnDelete();
            $table->string('event', 100);
            $table->string('status', 20);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->string('created_by', 20)->nullable();
            $table->string('updated_by', 20)->nullable();

            $table->index(['withdrawal_id', 'created_at']);
            $table->index('status');
        });

        DB::table('t_withdrawals')
            ->where('status', 'rejected')
            ->update([
                'status' => WithdrawalStatusConst::FAILED,
                'failure_reason' => DB::raw('COALESCE(failure_reason, rejection_reason)'),
            ]);

        DB::table('t_wallet_transactions')
            ->where('status', 'rejected')
            ->update(['status' => WalletTransactionStatusConst::FAILED]);
    }

    public function down(): void
    {
        Schema::dropIfExists('t_withdrawal_logs');

        Schema::table('t_wallet_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawal_id');
        });

        Schema::table('t_withdrawals', function (Blueprint $table) {
            $table->dropColumn(['failure_reason', 'gateway_reference', 'gateway_response']);
        });

        DB::table('t_withdrawals')
            ->where('status', WithdrawalStatusConst::FAILED)
            ->update(['status' => 'rejected']);

        DB::table('t_wallet_transactions')
            ->where('status', WalletTransactionStatusConst::FAILED)
            ->update(['status' => 'rejected']);
    }
};
