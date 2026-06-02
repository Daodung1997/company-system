<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Area;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $worker;

    protected $service;

    protected $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceCategory::factory()->create(['status' => 'active']);
        $this->area = Area::factory()->create(['status' => 'active']);

        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $this->worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);
    }

    public function test_customer_can_list_quotations()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        // Create some quotations
        Quotation::factory()->count(3)->create([
            'job_id' => $job->id,
            'status' => QuotationStatusConst::PENDING,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/jobs/{$job->id}/quotations");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'job_id',
                        'price',
                        'estimated_duration',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_customer_cannot_list_quotations_for_others_job()
    {
        $otherCustomer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $job = Job::factory()->create([
            'customer_id' => $otherCustomer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/jobs/{$job->id}/quotations");

        $response->assertStatus(403)
            ->assertJsonPath('messages.error_code', ExceptionCode::PERMISSION_DENIED);
    }

    public function test_customer_can_accept_quotation()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        $quotation = Quotation::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'price' => 500000,
            'status' => QuotationStatusConst::PENDING,
        ]);

        // Create another quotation that should be rejected
        $otherQuotation = Quotation::factory()->create([
            'job_id' => $job->id,
            'status' => QuotationStatusConst::PENDING,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/quotations/{$quotation->id}/accept");

        $response->assertStatus(200);

        // Verify job updated
        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'worker_id' => $this->worker->id,
            'quotation_price' => 500000,
            'status' => JobStatusConst::PENDING_PAYMENT,
        ]);

        // Verify accepted quotation
        $this->assertDatabaseHas('t_quotations', [
            'id' => $quotation->id,
            'status' => QuotationStatusConst::ACCEPTED,
        ]);

        // Verify other quotation rejected
        $this->assertDatabaseHas('t_quotations', [
            'id' => $otherQuotation->id,
            'status' => QuotationStatusConst::REJECTED,
        ]);
    }

    public function test_customer_cannot_accept_non_pending_quotation()
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
            ->postJson("/api/customer/jobs/{$job->id}/quotations/{$quotation->id}/accept");

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_STATUS);
    }

    public function test_customer_cannot_accept_quotation_on_wrong_status_job()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::IN_PROGRESS,
        ]);

        $quotation = Quotation::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'status' => QuotationStatusConst::PENDING,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/quotations/{$quotation->id}/accept");

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_STATUS);
    }

    public function test_guest_cannot_access_quotation_endpoints()
    {
        $job = Job::factory()->create();
        $quotation = Quotation::factory()->create([
            'job_id' => $job->id,
        ]);

        // List quotations
        $this->getJson("/api/customer/jobs/{$job->id}/quotations")
            ->assertStatus(401);

        // Accept quotation
        $this->postJson("/api/customer/jobs/{$job->id}/quotations/{$quotation->id}/accept", [])
            ->assertStatus(401);
    }
}
