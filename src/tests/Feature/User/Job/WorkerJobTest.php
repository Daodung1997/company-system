<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Complaint\ComplaintStatusConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Payment\PaymentMethodConst;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Constants\Master\Models\PlatformFee\PlatformFeeCodeConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Models\Area;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WorkerJobTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $worker;

    protected $customer;

    protected $service;

    protected $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceCategory::factory()->create(['status' => 'active']);
        $this->area = Area::factory()->create(['status' => 'active']);

        $this->customer = User::factory()->create([
            'role' => UserRoleConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $this->worker = User::factory()->create([
            'role' => UserRoleConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        // Create worker profile
        WorkerProfile::factory()->create([
            'user_id' => $this->worker->id,
            'profile_status' => \App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus::APPROVED,
        ]);
    }

    public function test_worker_can_list_available_jobs()
    {
        // Create a job matching worker's service/area
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'area_id' => $this->area->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs/available');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'code',
                            'service',
                            'description',
                            'area',
                            'scheduled_date',
                            'time_slot',
                            'status',
                        ],
                    ],
                ],
            ]);
    }

    public function test_worker_can_submit_quotation()
    {
        // Setup Platform Fee
        \App\Models\PlatformFee::create([
            'code' => PlatformFeeCodeConst::PLATFORM_FEE,
            'fee_type' => \App\Constants\Master\Models\PlatformFee\PlatformFeeTypeConst::PERCENTAGE,
            'amount' => 10, // 10%
            'name' => 'Job Fee',
            'description' => 'Job Fee Description',
            'start_date' => now()->subDay(),
            'status' => \App\Constants\Master\Models\PlatformFee\PlatformFeeStatusConst::ACTIVE,
        ]);

        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        $data = [
            'price' => 500000,
            'estimated_duration' => '2 hours',
            'note' => 'Can do it today',
        ];

        $response = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$job->id}/quotation", $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.price', 500000)
            ->assertJsonPath('data.platform_fee', 50000)
            ->assertJsonPath('data.total_amount', 550000)
            ->assertJsonPath('data.status', QuotationStatusConst::PENDING)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'job_id',
                    'price',
                    'platform_fee',
                    'total_amount',
                    'estimated_duration',
                    'note',
                    'status',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('t_quotations', [
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'price' => 500000,
            'platform_fee' => 50000,
            'total_amount' => 550000,
        ]);

        // Check job status updated
        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::QUOTED,
        ]);
    }

    public function test_worker_cannot_quote_twice()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        // Create existing quotation
        Quotation::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'status' => QuotationStatusConst::PENDING,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$job->id}/quotation", [
                'price' => 600000,
                'estimated_duration' => '3 hours',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('messages.error_code', ExceptionCode::DUPLICATE_ENTRY);
    }

    public function test_worker_cannot_quote_non_waiting_job()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$job->id}/quotation", [
                'price' => 500000,
                'estimated_duration' => '2 hours',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_STATUS);
    }

    public function test_worker_can_list_their_jobs()
    {
        // Create a job assigned to worker
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_worker_can_view_job_detail()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PAID,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson("/api/worker/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $job->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'service',
                    'description',
                    'area',
                    'scheduled_date',
                    'status',
                    'customer',
                    'address', // Visible because assigned
                ],
            ]);
    }

    public function test_worker_can_start_job()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PAID,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$job->id}/start");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', JobStatusConst::IN_PROGRESS);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::IN_PROGRESS,
        ]);
    }

    public function test_worker_cannot_start_non_paid_job()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$job->id}/start");

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_STATUS);
    }

    public function test_worker_can_complete_job()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::IN_PROGRESS,
            'quotation_price' => 500000,
        ]);

        // Create a paid payment for this job
        \Illuminate\Support\Facades\DB::table('t_payments')->insert([
            'job_id' => $job->id,
            'code' => 'PAY123',
            'amount' => 500000,
            'worker_earning' => 450000,
            'platform_fee' => 50000,
            'payment_method' => PaymentMethodConst::BANK_TRANSFER,
            'status' => PaymentStatusConst::PAID,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$job->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', JobStatusConst::COMPLETED);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::COMPLETED,
        ]);

        // Verify escrow transaction
        $this->assertDatabaseHas('t_wallet_transactions', [
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'amount' => 450000,
            'status' => WalletTransactionStatusConst::PENDING,
            'type' => WalletTransactionTypeConst::EARNING,
        ]);

        // Verify release_at is set
        $transaction = \App\Models\WalletTransaction::where('job_id', $job->id)->first();
        $this->assertNotNull($transaction->release_at);
        $this->assertTrue($transaction->release_at->isAfter(now()->addDays(2)));

        // Verify available balance is still 0 (since it's pending)
        $walletService = app(WalletService::class);
        $balance = $walletService->getBalance($this->worker);
        $this->assertEquals(0, $balance['available_balance']);
        $this->assertEquals(450000, $balance['pending_balance']);
    }

    public function test_unassigned_worker_cannot_start_job()
    {
        $otherWorker = User::factory()->create([
            'role' => UserRoleConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PAID,
        ]);

        $response = $this->actingAs($otherWorker, 'api')
            ->postJson("/api/worker/jobs/{$job->id}/start");

        $response->assertStatus(403)
            ->assertJsonPath('messages.error_code', ExceptionCode::PERMISSION_DENIED);
    }

    public function test_worker_can_reject_job()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$job->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('data.success', true);

        $this->assertDatabaseHas('t_job_invited_workers', [
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'status' => \App\Models\JobInvitedWorker::STATUS_REJECTED,
        ]);
    }

    public function test_worker_can_reply_complaint()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::COMPLAINT,
        ]);

        $complaintId = \Illuminate\Support\Facades\DB::table('t_complaints')->insertGetId([
            'job_id' => $job->id,
            'code' => 'CP-'.time(),
            'created_by' => $this->customer->id,
            'status' => ComplaintStatusConst::PENDING,
            'created_at' => now(),
            'updated_at' => now(),
            'description' => 'Test complaint',
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$job->id}/complaints/{$complaintId}/reply", [
                'note' => 'I have resolved it',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success', true);

        $this->assertDatabaseHas('t_complaints', [
            'id' => $complaintId,
            'worker_note' => 'I have resolved it',
        ]);
    }

    public function test_guest_cannot_access_worker_endpoints()
    {
        $job = Job::factory()->create();

        // List available
        $this->getJson('/api/worker/jobs/available')
            ->assertStatus(401);

        // List my jobs
        $this->getJson('/api/worker/jobs')
            ->assertStatus(401);

        // View detail
        $this->getJson("/api/worker/jobs/{$job->id}")
            ->assertStatus(401);

        // Submit quotation
        $this->postJson("/api/worker/jobs/{$job->id}/quotation", [])
            ->assertStatus(401);

        // Start job
        $this->postJson("/api/worker/jobs/{$job->id}/start", [])
            ->assertStatus(401);

        // Complete job
        $this->postJson("/api/worker/jobs/{$job->id}/complete", [])
            ->assertStatus(401);
    }
}
