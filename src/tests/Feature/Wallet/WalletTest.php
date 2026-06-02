<?php

namespace Tests\Feature\Wallet;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Models\Job;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WorkerProfile;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = User::factory()->worker()->create();
        WorkerProfile::factory()->approved()->create([
            'user_id' => $this->worker->id,
        ]);
    }

    public function test_worker_can_view_wallet_balance()
    {
        $job = Job::factory()->create([
            'worker_id' => $this->worker->id,
        ]);

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 500000,
        ]);

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'job_id' => $job->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::PENDING,
            'amount' => 200000,
        ]);

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::WITHDRAWAL,
            'status' => WalletTransactionStatusConst::COMPLETED,
            'amount' => 100000,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/wallet');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'available_balance',
                    'pending_balance',
                    'total_earnings',
                    'total_withdrawn',
                ],
            ])
            ->assertJsonPath('data.available_balance', 400000)
            ->assertJsonPath('data.pending_balance', 200000)
            ->assertJsonPath('data.total_earnings', 700000)
            ->assertJsonPath('data.total_withdrawn', 100000);
    }

    public function test_worker_can_list_transactions()
    {
        WalletTransaction::factory()->count(3)->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/wallet/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'code', 'type', 'amount', 'status', 'created_at'],
                    ],
                    'total', 'current_page', 'limit',
                ],
            ]);
    }

    public function test_worker_can_filter_transactions_by_type()
    {
        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
        ]);

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::WITHDRAWAL,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/wallet/transactions?type=earning');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
    }

    public function test_worker_transactions_are_scoped_to_authenticated_worker()
    {
        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 400000,
        ]);

        $otherWorker = User::factory()->worker()->create();
        WorkerProfile::factory()->approved()->create([
            'user_id' => $otherWorker->id,
        ]);

        WalletTransaction::factory()->create([
            'worker_id' => $otherWorker->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 999999,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/wallet/transactions?type=earning&per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1);

        $this->assertEquals(400000.0, $response->json('data.data.0.amount'));
    }

    public function test_guest_cannot_access_wallet()
    {
        $response = $this->getJson('/api/worker/wallet');
        $response->assertStatus(401);
    }

    public function test_customer_cannot_access_wallet()
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer, 'api')
            ->getJson('/api/worker/wallet');

        $response->assertStatus(403);
    }

    public function test_unapproved_worker_cannot_access_wallet()
    {
        $pendingWorker = User::factory()->worker()->create();
        WorkerProfile::factory()->create([
            'user_id' => $pendingWorker->id,
        ]);

        $response = $this->actingAs($pendingWorker, 'api')
            ->getJson('/api/worker/wallet');

        $response->assertStatus(403)
            ->assertJsonPath('messages.error_code', ExceptionCode::WORKER_NOT_APPROVED)
            ->assertJsonPath('messages.message', 'Chưa được duyệt');
    }

    public function test_credit_worker_releases_existing_pending_transaction_instead_of_creating_duplicate()
    {
        $job = Job::factory()->create([
            'worker_id' => $this->worker->id,
        ]);

        WalletTransaction::factory()->create([
            'worker_id' => $this->worker->id,
            'job_id' => $job->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::PENDING,
            'amount' => 500000,
        ]);

        app(WalletService::class)->creditWorker(
            $this->worker->id,
            500000,
            'Job #JOB321 completed',
            $job->id
        );

        $this->assertDatabaseCount('t_wallet_transactions', 1);
        $this->assertDatabaseHas('t_wallet_transactions', [
            'worker_id' => $this->worker->id,
            'job_id' => $job->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'amount' => 500000,
        ]);
    }
}
