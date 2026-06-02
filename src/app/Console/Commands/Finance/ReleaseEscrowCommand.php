<?php

namespace App\Console\Commands\Finance;

use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleaseEscrowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:release-escrow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release pending funds to worker wallets after escrow period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting escrow release process...');

        $transactions = WalletTransaction::query()
            ->where('status', WalletTransactionStatusConst::PENDING)
            ->whereNotNull('release_at')
            ->where('release_at', '<=', now())
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No transactions eligible for release.');

            return;
        }

        $this->info("Found {$transactions->count()} transactions to release.");

        foreach ($transactions as $transaction) {
            DB::beginTransaction();
            try {
                // Refresh and lock for update
                $transaction = WalletTransaction::query()
                    ->where('id', $transaction->id)
                    ->lockForUpdate()
                    ->first();

                if (! $transaction || $transaction->status !== WalletTransactionStatusConst::PENDING) {
                    DB::rollBack();

                    continue;
                }

                $transaction->update([
                    'status' => WalletTransactionStatusConst::RELEASED,
                    'updated_by' => 'SYSTEM_CRON',
                ]);

                DB::commit();
                $this->line("Released transaction #{$transaction->code} for worker #{$transaction->worker_id}");
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Failed to release transaction #{$transaction->id}: ".$e->getMessage());
                $this->error("Error releasing transaction #{$transaction->id}");
            }
        }

        $this->info('Escrow release process completed.');
    }
}
