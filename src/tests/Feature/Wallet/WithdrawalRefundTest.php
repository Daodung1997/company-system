<?php

namespace Tests\Feature\Wallet;

use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Repositories\Wallet\WalletTransactionRepository;
use App\Services\Wallet\WithdrawalGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalRefundTest extends TestCase
{
    use RefreshDatabase;

    protected $worker;

    protected $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create worker and bank account
        $this->worker = User::factory()->create(['role' => 'worker']);
        $this->bankAccount = BankAccount::factory()->create([
            'user_id' => $this->worker->id,
        ]);

        // 2. Seed initial balance (500,000 VND)
        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 500000,
        ]);
    }

    /**
     * Test scenario: Withdrawal fails at gateway and balance is restored correctly.
     */
    public function test_withdrawal_failure_restores_worker_balance()
    {
        $withdrawalAmount = 200000;

        // STEP 1: Mock Gateway to return failure BEFORE creating request
        // This ensures even if the job runs automatically (sync queue), it will fail.
        $this->app->instance(WithdrawalGatewayService::class, new class extends WithdrawalGatewayService
        {
            public function transfer(Withdrawal $withdrawal): array
            {
                return [
                    'success' => false,
                    'failure_reason' => 'Simulated Gateway Error',
                    'gateway_reference' => 'REF-ERROR-123',
                ];
            }
        });

        // STEP 2: Worker creates withdrawal request
        $response = $this->actingAs($this->worker, 'api')
            ->postJson('/api/worker/wallet/withdrawals', [
                'amount' => $withdrawalAmount,
                'bank_account_id' => $this->bankAccount->id,
            ]);

        $response->assertStatus(201);
        $withdrawalId = $response->json('data.id');

        // STEP 3: VERIFY FINAL STATE

        // 1. Withdrawal status must be FAILED
        $this->assertDatabaseHas('t_withdrawals', [
            'id' => $withdrawalId,
            'status' => WithdrawalStatusConst::FAILED,
            'failure_reason' => 'Simulated Gateway Error',
        ]);

        // 2. Wallet transaction status must be FAILED
        $this->assertDatabaseHas('t_wallet_transactions', [
            'withdrawal_id' => $withdrawalId,
            'status' => WalletTransactionStatusConst::FAILED,
        ]);

        // 3. CRITICAL: Available balance must be back to 500,000 VND
        $this->assertEquals(500000, app(WalletTransactionRepository::class)->calculateAvailableBalance($this->worker->id));

        // 4. Withdrawal log must be created
        $this->assertDatabaseHas('t_withdrawal_logs', [
            'withdrawal_id' => $withdrawalId,
            'event' => 'payout_failed',
            'status' => WithdrawalStatusConst::FAILED,
        ]);
    }
}
