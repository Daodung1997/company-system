<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Job\JobTimeSlotConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Area;
use App\Models\CustomerProfile;
use App\Models\Job;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerArea;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class JobFlowTest extends TestCase
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

        CustomerProfile::create([
            'user_id' => $this->customer->id,
            'phone' => '0901234567',
            'area_id' => $this->area->id,
        ]);

        $this->worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $workerProfile = WorkerProfile::factory()->create([
            'user_id' => $this->worker->id,
            'availability' => true,
            'activity_status' => 'active',
            'profile_status' => 'approved',
            'avg_rating' => 5.0,
            'total_completed_jobs' => 10,
        ]);

        WorkerService::create([
            'worker_profile_id' => $workerProfile->id,
            'service_category_id' => $this->service->id,
        ]);

        WorkerArea::create([
            'worker_profile_id' => $workerProfile->id,
            'area_id' => $this->area->id,
        ]);
    }

    public function test_end_to_end_job_flow()
    {
        // 1. Customer creates a job
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'May laptop is broken',
            'area_id' => $this->area->id,
            'address' => '123 Test Street',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $responseCreate = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $jobData);

        $responseCreate->assertStatus(201)
            ->assertJsonPath('data.status', JobStatusConst::WAITING_FOR_QUOTATION);

        $jobId = $responseCreate->json('data.id');

        // 2. Worker submits quotation
        $quotationData = [
            'price' => 500000,
            'estimated_duration' => '2 hours',
            'note' => 'I can fix it fast',
        ];

        $responseQuotation = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$jobId}/quotation", $quotationData);

        $responseQuotation->assertStatus(201);
        $quotationId = $responseQuotation->json('data.id');

        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'status' => JobStatusConst::QUOTED,
        ]);

        // 3. Customer accepts quotation
        $responseAccept = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$jobId}/quotations/{$quotationId}/accept");

        $responseAccept->assertStatus(200)
            ->assertJsonPath('data.status', JobStatusConst::PENDING_PAYMENT);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
        ]);

        // 3.5. Customer pays for job
        $responsePay = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$jobId}/payment/cash");

        $responsePay->assertStatus(200);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'status' => JobStatusConst::PAID,
        ]);

        // 4. Worker starts job
        $responseStart = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$jobId}/start");

        $responseStart->assertStatus(200);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'status' => JobStatusConst::IN_PROGRESS,
        ]);

        // 5. Worker completes job
        $responseComplete = $this->actingAs($this->worker, 'api')
            ->postJson("/api/worker/jobs/{$jobId}/complete");

        $responseComplete->assertStatus(200);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'status' => JobStatusConst::COMPLETED,
        ]);
    }
}
