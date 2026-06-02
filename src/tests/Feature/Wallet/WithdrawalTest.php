<?php

namespace Tests\Feature\Wallet;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Repositories\Wallet\WalletTransactionRepository;
use App\Services\Wallet\WithdrawalGatewayService;
use App\Services\Wallet\WithdrawalProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    protected $worker;

    protected $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = User::factory()->create(['role' => 'worker']);
        $this->bankAccount = BankAccount::factory()->create([
            'user_id' => $this->worker->id,
            'is_default' => true,
        ]);
    }

    public function test_worker_can_create_withdrawal()
    {
        Queue::fake();

        // Create available balance
        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 500000,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson('/api/worker/wallet/withdrawals', [
                'amount' => 300000,
                'bank_account_id' => $this->bankAccount->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', 300000)
            ->assertJsonPath('data.status', WithdrawalStatusConst::REQUESTED);

        $this->assertDatabaseHas('t_withdrawals', [
            'worker_id' => $this->worker->id,
            'amount' => 300000,
            'status' => WithdrawalStatusConst::REQUESTED,
            'bank_account_id' => $this->bankAccount->id,
        ]);

        $this->assertDatabaseHas('t_wallet_transactions', [
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::WITHDRAWAL,
            'status' => WalletTransactionStatusConst::PENDING,
            'amount' => 300000,
        ]);

        $this->assertSame(
            200000.0,
            app(WalletTransactionRepository::class)->calculateAvailableBalance($this->worker->id)
        );

        Queue::assertPushed(\App\Jobs\ProcessWithdrawalJob::class);
    }

    public function test_cannot_withdraw_with_insufficient_balance()
    {
        Queue::fake();

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 100000,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson('/api/worker/wallet/withdrawals', [
                'amount' => 200000,
                'bank_account_id' => $this->bankAccount->id,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INSUFFICIENT_BALANCE);
    }

    public function test_cannot_withdraw_with_pending_withdrawal()
    {
        Queue::fake();

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 500000,
        ]);

        // Create pending withdrawal
        Withdrawal::factory()->create([
            'worker_id' => $this->worker->id,
            'status' => WithdrawalStatusConst::REQUESTED,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson('/api/worker/wallet/withdrawals', [
                'amount' => 100000,
                'bank_account_id' => $this->bankAccount->id,
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('messages.error_code', ExceptionCode::PENDING_WITHDRAWAL_EXISTS);
    }

    public function test_cannot_withdraw_without_bank_account()
    {
        Queue::fake();

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 500000,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson('/api/worker/wallet/withdrawals', [
                'amount' => 100000,
                'bank_account_id' => 99999, // Non-existent
            ]);

        $response->assertStatus(422);
    }

    public function test_worker_can_list_withdrawals()
    {
        Withdrawal::factory()->count(3)->create([
            'worker_id' => $this->worker->id,
        ]);
        Withdrawal::factory()->count(2)->create();

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/wallet/withdrawals');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 3)
            ->assertJsonCount(3, 'data.data')
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'code', 'amount', 'status', 'created_at'],
                    ],
                    'total', 'current_page', 'limit',
                ],
            ]);
    }

    public function test_worker_can_get_withdrawal_detail()
    {
        $withdrawal = Withdrawal::factory()->create([
            'worker_id' => $this->worker->id,
            'bank_account_id' => $this->bankAccount->id,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson("/api/worker/wallet/withdrawals/{$withdrawal->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $withdrawal->id);
    }

    public function test_worker_cannot_view_others_withdrawal()
    {
        $otherWorker = User::factory()->create(['role' => 'worker']);
        $withdrawal = Withdrawal::factory()->create([
            'worker_id' => $otherWorker->id,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson("/api/worker/wallet/withdrawals/{$withdrawal->id}");

        $response->assertStatus(404);
    }

    public function test_processing_withdrawal_marks_transaction_completed_on_success()
    {
        Queue::fake();

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 500000,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson('/api/worker/wallet/withdrawals', [
                'amount' => 300000,
                'bank_account_id' => $this->bankAccount->id,
            ]);

        $withdrawalId = $response->json('data.id');

        $this->app->instance(WithdrawalGatewayService::class, new class extends WithdrawalGatewayService
        {
            public function transfer(Withdrawal $withdrawal): array
            {
                return [
                    'success' => true,
                    'gateway_reference' => 'GW-SUCCESS-1',
                    'response' => ['message' => 'ok'],
                ];
            }
        });

        app(WithdrawalProcessingService::class)->process($withdrawalId);

        $this->assertDatabaseHas('t_withdrawals', [
            'id' => $withdrawalId,
            'status' => WithdrawalStatusConst::COMPLETED,
            'gateway_reference' => 'GW-SUCCESS-1',
        ]);

        $this->assertDatabaseHas('t_wallet_transactions', [
            'worker_id' => $this->worker->id,
            'withdrawal_id' => $withdrawalId,
            'type' => WalletTransactionTypeConst::WITHDRAWAL,
            'status' => WalletTransactionStatusConst::COMPLETED,
            'amount' => 300000,
        ]);

        $this->assertDatabaseHas('t_withdrawal_logs', [
            'withdrawal_id' => $withdrawalId,
            'event' => 'payout_completed',
            'status' => WithdrawalStatusConst::COMPLETED,
        ]);
    }

    public function test_processing_withdrawal_marks_failed_and_restores_available_balance()
    {
        Queue::fake();

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 500000,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson('/api/worker/wallet/withdrawals', [
                'amount' => 300000,
                'bank_account_id' => $this->bankAccount->id,
            ]);

        $withdrawalId = $response->json('data.id');

        $this->app->instance(WithdrawalGatewayService::class, new class extends WithdrawalGatewayService
        {
            public function transfer(Withdrawal $withdrawal): array
            {
                return [
                    'success' => false,
                    'gateway_reference' => 'GW-FAILED-1',
                    'failure_reason' => 'Bank timeout',
                    'response' => ['message' => 'timeout'],
                ];
            }
        });

        app(WithdrawalProcessingService::class)->process($withdrawalId);

        $this->assertDatabaseHas('t_withdrawals', [
            'id' => $withdrawalId,
            'status' => WithdrawalStatusConst::FAILED,
            'failure_reason' => 'Bank timeout',
            'gateway_reference' => 'GW-FAILED-1',
        ]);

        $this->assertDatabaseHas('t_wallet_transactions', [
            'worker_id' => $this->worker->id,
            'withdrawal_id' => $withdrawalId,
            'type' => WalletTransactionTypeConst::WITHDRAWAL,
            'status' => WalletTransactionStatusConst::FAILED,
            'amount' => 300000,
        ]);

        $this->assertDatabaseHas('t_withdrawal_logs', [
            'withdrawal_id' => $withdrawalId,
            'event' => 'payout_failed',
            'status' => WithdrawalStatusConst::FAILED,
        ]);

        $this->assertSame(
            500000.0,
            app(WalletTransactionRepository::class)->calculateAvailableBalance($this->worker->id)
        );
    }
}
