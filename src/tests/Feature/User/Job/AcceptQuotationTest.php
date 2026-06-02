<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\PlatformFee\PlatformFeeCodeConst;
use App\Constants\Master\Models\PlatformFee\PlatformFeeTypeConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Models\Job;
use App\Models\PlatformFee;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptQuotationTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;

    protected $worker;

    protected $job;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Users
        $this->customer = User::factory()->create(['role' => CommonRolesConst::CUSTOMER]);
        $this->worker = User::factory()->create(['role' => CommonRolesConst::WORKER]);

        // Setup Job
        $this->job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);
    }

    public function test_customer_can_accept_quotation_and_fee_is_calculated()
    {
        // 1. Setup Platform Fee
        PlatformFee::create([
            'code' => PlatformFeeCodeConst::PLATFORM_FEE,
            'fee_type' => PlatformFeeTypeConst::PERCENTAGE,
            'amount' => 10, // 10%
            'name' => 'Job Fee',
            'description' => 'Job Fee Description',
            'start_date' => now()->subDay(),
            'status' => \App\Constants\Master\Models\PlatformFee\PlatformFeeStatusConst::ACTIVE,
        ]);

        // 2. Setup Quotation
        $quotation = Quotation::factory()->create([
            'job_id' => $this->job->id,
            'worker_id' => $this->worker->id,
            'price' => 100000,
            'platform_fee' => 10000,
            'total_amount' => 110000,
            'status' => QuotationStatusConst::PENDING,
        ]);

        // 3. Action: Accept
        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$this->job->id}/quotations/{$quotation->id}/accept");

        // 4. Assert Response
        $response->assertStatus(200);

        // 5. Assert Database changes
        // Fee = 10% of 100,000 = 10,000
        // Total = 110,000
        $this->assertDatabaseHas('t_jobs', [
            'id' => $this->job->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
            'worker_id' => $this->worker->id,
            'quotation_price' => 100000,
            'platform_fee' => 10000,
            'total_amount' => 110000,
        ]);

        $this->assertDatabaseHas('t_quotations', [
            'id' => $quotation->id,
            'status' => QuotationStatusConst::ACCEPTED,
        ]);
    }

    public function test_other_quotations_are_rejected()
    {
        // Setup Platform Fee (Mocking generic fallback if needed, or specific)
        PlatformFee::create([
            'code' => PlatformFeeCodeConst::PLATFORM_FEE,
            'amount' => 0,
            'name' => 'Job Fee',
            'description' => 'Fee',
            'start_date' => now()->subDay(),
            'status' => \App\Constants\Master\Models\PlatformFee\PlatformFeeStatusConst::ACTIVE,
            'fee_type' => PlatformFeeTypeConst::FIXED,
        ]);

        $quotation1 = Quotation::factory()->create([
            'job_id' => $this->job->id,
            'worker_id' => $this->worker->id,
            'status' => QuotationStatusConst::PENDING,
        ]);

        $otherWorker = User::factory()->create(['role' => CommonRolesConst::WORKER]);
        $quotation2 = Quotation::factory()->create([
            'job_id' => $this->job->id,
            'worker_id' => $otherWorker->id,
            'status' => QuotationStatusConst::PENDING,
        ]);

        $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$this->job->id}/quotations/{$quotation1->id}/accept");

        $this->assertDatabaseHas('t_quotations', [
            'id' => $quotation1->id,
            'status' => QuotationStatusConst::ACCEPTED,
        ]);

        $this->assertDatabaseHas('t_quotations', [
            'id' => $quotation2->id,
            'status' => QuotationStatusConst::REJECTED,
        ]);
    }
}
