<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RejectQuotationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $this->worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);
    }

    public function test_customer_can_reject_pending_quotation()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        $quotation = Quotation::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'status' => QuotationStatusConst::PENDING,
            'price' => 500000,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/quotations/{$quotation->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', QuotationStatusConst::REJECTED);

        $this->assertDatabaseHas('t_quotations', [
            'id' => $quotation->id,
            'status' => QuotationStatusConst::REJECTED,
        ]);
    }

    public function test_customer_cannot_reject_accepted_quotation()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        $quotation = Quotation::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'status' => QuotationStatusConst::ACCEPTED,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/quotations/{$quotation->id}/reject");

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_STATUS);
    }

    public function test_customer_cannot_reject_already_rejected_quotation()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        $quotation = Quotation::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'status' => QuotationStatusConst::REJECTED,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/quotations/{$quotation->id}/reject");

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_STATUS);
    }

    public function test_customer_cannot_reject_quotation_of_others_job()
    {
        $otherCustomer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $job = Job::factory()->create([
            'customer_id' => $otherCustomer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        $quotation = Quotation::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'status' => QuotationStatusConst::PENDING,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/quotations/{$quotation->id}/reject");

        $response->assertStatus(403)
            ->assertJsonPath('messages.error_code', ExceptionCode::PERMISSION_DENIED);
    }
}
