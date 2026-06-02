<?php

namespace Tests\Feature\Console\Finance;

use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WorkerProfile;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseEscrowCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = User::factory()->create([
            'role' => UserRoleConst::WORKER,
        ]);

        WorkerProfile::factory()->create([
            'user_id' => $this->worker->id,
            'profile_status' => WorkerProfileStatus::APPROVED,
        ]);
    }

    public function test_command_releases_eligible_transactions()
    {
        // 1. Create a transaction that is due (release_at in the past)
        $dueTransaction = WalletTransaction::create([
            'worker_id' => $this->worker->id,
            'amount' => 500000,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::PENDING,
            'release_at' => now()->subMinutes(1),
            'description' => 'Due transaction',
            'created_by' => 'TEST',
            'updated_by' => 'TEST',
        ]);

        // 2. Create a transaction that is NOT due (release_at in the future)
        $futureTransaction = WalletTransaction::create([
            'worker_id' => $this->worker->id,
            'amount' => 300000,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::PENDING,
            'release_at' => now()->addDays(1),
            'description' => 'Future transaction',
            'created_by' => 'TEST',
            'updated_by' => 'TEST',
        ]);

        // Run the command
        $this->artisan('finance:release-escrow')
            ->expectsOutput('Starting escrow release process...')
            ->expectsOutput('Found 1 transactions to release.')
            ->expectsOutput("Released transaction #{$dueTransaction->code} for worker #{$this->worker->id}")
            ->expectsOutput('Escrow release process completed.')
            ->assertExitCode(0);

        // Verify due transaction is released
        $this->assertDatabaseHas('t_wallet_transactions', [
            'id' => $dueTransaction->id,
            'status' => WalletTransactionStatusConst::RELEASED,
        ]);

        // Verify future transaction is still pending
        $this->assertDatabaseHas('t_wallet_transactions', [
            'id' => $futureTransaction->id,
            'status' => WalletTransactionStatusConst::PENDING,
        ]);

        // Verify balance
        $walletService = app(WalletService::class);
        $balance = $walletService->getBalance($this->worker);
        $this->assertEquals(500000, $balance['available_balance']);
        $this->assertEquals(300000, $balance['pending_balance']);
    }
}
