<?php

namespace Tests\Feature\Admin\Finance;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Models\Job;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class PaymentTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase, WithFaker;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminWithAllPermissions();
    }

    public function test_admin_can_list_payments()
    {
        Payment::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/finance/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'amount', 'status', 'job'],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_view_payment_detail()
    {
        $payment = Payment::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/finance/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $payment->id)
            ->assertJsonStructure(['data' => ['job', 'created_at']]);
    }

    public function test_admin_can_refund_payment()
    {
        // 1. Setup Data
        $job = Job::factory()->create(['status' => JobStatusConst::PAID]);
        $payment = Payment::factory()->create([
            'job_id' => $job->id,
            'status' => PaymentStatusConst::PAID,
            'amount' => 100000,
        ]);

        // 2. Setup Worker Wallet Transaction
        $walletTxn = WalletTransaction::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $job->worker_id ?? User::factory()->create()->id, // Ensure worker exists
            'type' => \App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::PENDING,
            'amount' => 80000, // e.g. after platform fee
        ]);

        // 3. Perform Action
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/finance/payments/{$payment->id}/refund", [
                'reason' => 'Customer complaint valid',
            ]);

        // 4. Assertions
        $response->assertStatus(200);

        // Check Payment Status
        $this->assertDatabaseHas('t_payments', [
            'id' => $payment->id,
            'status' => PaymentStatusConst::REFUNDED,
            'refunded_amount' => 100000,
        ]);

        // Check Job Status
        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::REFUNDED,
        ]);

        // Check Wallet Transaction Deleted (Soft delete or hard delete depending on model, assuming hard delete based on service implementation)
        $this->assertDatabaseMissing('t_wallet_transactions', [
            'id' => $walletTxn->id,
        ]);
    }

    public function test_admin_cannot_refund_unpaid_payment()
    {
        $job = Job::factory()->create(['status' => JobStatusConst::WAITING_FOR_QUOTATION]);
        $payment = Payment::factory()->create([
            'job_id' => $job->id,
            'status' => PaymentStatusConst::PENDING,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/finance/payments/{$payment->id}/refund", [
                'reason' => 'Invalid',
            ]);

        $response->assertStatus(400); // ExceptionCode::INVALID_STATUS mapped to 400 in Handler usually, or 500 if not handled. Service throws BusinessException check handler.
        // Assuming Handler maps BusinessException to appropriate code. Service threw 400.
    }
}
