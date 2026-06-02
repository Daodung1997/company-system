<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Job\JobTimeSlotConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Area;
use App\Models\Configuration;
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

class JobFlowEdgeCaseTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $workerA;

    protected $workerB;

    protected $service;

    protected $areaA;

    protected $areaB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceCategory::factory()->create(['status' => 'active']);
        $this->areaA = Area::factory()->create(['status' => 'active', 'name' => 'Khu vuc A']);
        $this->areaB = Area::factory()->create(['status' => 'active', 'name' => 'Khu vuc B']);

        // Setup Customer in Area A
        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);
        CustomerProfile::create([
            'user_id' => $this->customer->id,
            'phone' => '0901234567',
            'area_id' => $this->areaA->id,
        ]);

        // Setup Worker A in Area A
        $this->workerA = $this->createWorkerInArea($this->areaA);

        // Setup Worker B in Area B
        $this->workerB = $this->createWorkerInArea($this->areaB);

        // Required for distribution configuration
        Configuration::updateOrCreate(
            ['key' => 'job_assignment_config'],
            [
                'value' => json_encode([
                    'scan_radius' => 10,
                    'max_workers_per_job' => 5,
                    'rating_weight' => 0.5,
                    'distance_weight' => 0.3,
                    'response_rate_weight' => 0.2,
                ]),
            ]
        );
    }

    private function createWorkerInArea($area)
    {
        $worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $workerProfile = WorkerProfile::factory()->create([
            'user_id' => $worker->id,
            'availability' => true,
            'activity_status' => 'active',
            'profile_status' => 'approved',
            'avg_rating' => 4.5,
            'total_completed_jobs' => 10,
        ]);

        WorkerService::create([
            'worker_profile_id' => $workerProfile->id,
            'service_category_id' => $this->service->id,
        ]);

        WorkerArea::create([
            'worker_profile_id' => $workerProfile->id,
            'area_id' => $area->id,
        ]);

        return $worker;
    }

    public function test_worker_sees_only_jobs_in_their_area()
    {
        // Customer creates Job in Area A
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Job in Area A',
            'area_id' => $this->areaA->id,
            'address' => '123 Test Street',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $responseCreate = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $jobData);

        $responseCreate->assertStatus(201);
        $jobIdAreaA = $responseCreate->json('data.id');

        // Worker A (in Area A) checks available jobs -> Should see Job
        $responseWorkerA = $this->actingAs($this->workerA, 'api')
            ->getJson('/api/worker/jobs/available');

        $responseWorkerA->assertStatus(200);
        $dataWorkerA = collect($responseWorkerA->json('data.data'));
        $this->assertTrue($dataWorkerA->contains('id', $jobIdAreaA), 'Worker A should see Job A');

        // Worker B (in Area B) checks available jobs -> Should NOT see Job (because is_invited = false & area doesn\'t match)
        $responseWorkerB = $this->actingAs($this->workerB, 'api')
            ->getJson('/api/worker/jobs/available');

        $responseWorkerB->assertStatus(200);
        $dataWorkerB = collect($responseWorkerB->json('data.data'));
        $this->assertFalse($dataWorkerB->contains('id', $jobIdAreaA), 'Worker B should NOT see Job A');
    }

    public function test_only_accepted_worker_can_start_and_complete()
    {
        // 1. We need 2 workers in the same Area to quote on the same job
        $workerC = $this->createWorkerInArea($this->areaA);

        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Job Needs Fixing',
            'area_id' => $this->areaA->id,
            'address' => 'Address A',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $createRes = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData);
        $createRes->assertStatus(201);
        $jobId = $createRes->json('data.id');

        // 2. Worker A and Worker C submit quotations
        $quoteA = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 500000,
            'estimated_duration' => '1 hour',
        ]);
        $quoteA->assertStatus(201);
        $quotationIdA = $quoteA->json('data.id');

        $quoteC = $this->actingAs($workerC, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 450000,
            'estimated_duration' => '2 hours',
        ]);
        $quoteC->assertStatus(201);
        $quotationIdC = $quoteC->json('data.id');

        // 3. Customer accepts Worker A's quote
        $acceptRes = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$jobId}/quotations/{$quotationIdA}/accept");
        $acceptRes->assertStatus(200);

        // Job is now assigned to worker A (status PENDING_PAYMENT initially due to bypassed payment phase)
        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'worker_id' => $this->workerA->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
        ]);

        // 3.5 Pay
        $this->actingAs($this->customer, 'api')->postJson("/api/customer/jobs/{$jobId}/payment/cash")->assertStatus(200);

        // 4. Edge Case: Worker C (rejected) tries to start work => expects 403
        $startByC = $this->actingAs($workerC, 'api')->postJson("/api/worker/jobs/{$jobId}/start");
        $startByC->assertStatus(403);

        // 5. Worker A starts work => expects 200
        $startByA = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/start");
        $startByA->assertStatus(200);

        // 6. Edge Case: Worker C (rejected) tries to complete work => expects 403
        $completeByC = $this->actingAs($workerC, 'api')->postJson("/api/worker/jobs/{$jobId}/complete");
        $completeByC->assertStatus(403);

        // 7. Worker A completes work => expects 200
        $completeByA = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/complete");
        $completeByA->assertStatus(200);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'status' => JobStatusConst::COMPLETED,
        ]);
    }

    public function test_customer_cancel_job_before_assigned()
    {
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Job to cancel',
            'area_id' => $this->areaA->id,
            'address' => 'Test',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $createRes = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData);
        $jobId = $createRes->json('data.id');

        $cancelRes = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$jobId}/cancel", ['reason' => 'Changed my mind']);
        $cancelRes->assertStatus(200);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'status' => JobStatusConst::CANCELLED,
            'cancelled_reason' => 'Changed my mind',
        ]);
    }

    public function test_customer_cannot_cancel_job_after_assigned()
    {
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Cannot cancel this',
            'area_id' => $this->areaA->id,
            'address' => 'Test',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $jobId = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData)->json('data.id');
        $quotationId = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 100000, 'estimated_duration' => '1h',
        ])->json('data.id');
        $acceptRes = $this->actingAs($this->customer, 'api')->postJson("/api/customer/jobs/{$jobId}/quotations/{$quotationId}/accept");
        $acceptRes->assertStatus(200);

        $cancelRes = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$jobId}/cancel", ['reason' => 'Too late']);
        $cancelRes->assertStatus(400); // 400 Bad Request
    }

    public function test_worker_cannot_quote_twice_on_same_job()
    {
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Double quote test',
            'area_id' => $this->areaA->id,
            'address' => 'Test',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $jobId = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData)->json('data.id');

        // First quote
        $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 100000, 'estimated_duration' => '1h',
        ])->assertStatus(201);

        // Second quote -> expects 409
        $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 150000, 'estimated_duration' => '2h',
        ])->assertStatus(409);
    }

    public function test_worker_cannot_quote_after_job_assigned()
    {
        $workerC = $this->createWorkerInArea($this->areaA);

        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Late quote test',
            'area_id' => $this->areaA->id,
            'address' => 'Test',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $jobId = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData)->json('data.id');

        $quotationId = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 100000, 'estimated_duration' => '1h',
        ])->json('data.id');

        $acceptRes = $this->actingAs($this->customer, 'api')->postJson("/api/customer/jobs/{$jobId}/quotations/{$quotationId}/accept");
        $acceptRes->assertStatus(200);

        // Job is PENDING_PAYMENT. Worker C tries to quote -> expects 400
        $this->actingAs($workerC, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 150000, 'estimated_duration' => '2h',
        ])->assertStatus(400);
    }

    public function test_worker_cannot_complete_unstarted_job()
    {
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Invalid transition',
            'area_id' => $this->areaA->id,
            'address' => 'Test',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $jobId = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData)->json('data.id');

        $quotationId = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 100000, 'estimated_duration' => '1h',
        ])->json('data.id');

        $acceptRes = $this->actingAs($this->customer, 'api')->postJson("/api/customer/jobs/{$jobId}/quotations/{$quotationId}/accept");
        $acceptRes->assertStatus(200);

        // Job is PENDING_PAYMENT not IN_PROGRESS. Completing should fail -> expects 400
        $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/complete")->assertStatus(400);
    }

    public function test_customer_can_reject_quotation()
    {
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Reject quote',
            'area_id' => $this->areaA->id,
            'address' => 'Test',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $jobId = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData)->json('data.id');

        $quotationId = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 500000, 'estimated_duration' => '1h',
        ])->json('data.id');

        $rejectRes = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$jobId}/quotations/{$quotationId}/reject");

        $rejectRes->assertStatus(200);

        $this->assertDatabaseHas('t_quotations', [
            'id' => $quotationId,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'status' => JobStatusConst::QUOTED,
        ]);
    }

    public function test_customer_complaint_flow()
    {
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Complaint target',
            'area_id' => $this->areaA->id,
            'address' => 'Test',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $jobId = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData)->json('data.id');
        $quotationId = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 100000, 'estimated_duration' => '1h',
        ])->json('data.id');

        $acceptRes = $this->actingAs($this->customer, 'api')->postJson("/api/customer/jobs/{$jobId}/quotations/{$quotationId}/accept");
        $acceptRes->assertStatus(200);

        $this->actingAs($this->customer, 'api')->postJson("/api/customer/jobs/{$jobId}/payment/cash")->assertStatus(200);

        $startRes = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/start");
        $startRes->assertStatus(200);

        // Complete job
        $completeRes = $this->actingAs($this->workerA, 'api')->postJson("/api/worker/jobs/{$jobId}/complete");
        $completeRes->assertStatus(200);

        // Complaint
        $complaintRes = $this->actingAs($this->customer, 'api')->postJson("/api/customer/jobs/{$jobId}/complaint", [
            'content' => 'Very bad service',
        ]);

        $complaintRes->assertStatus(200);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $jobId,
            'status' => JobStatusConst::COMPLAINT,
        ]);

        $this->assertDatabaseHas('t_complaints', [
            'job_id' => $jobId,
            'description' => 'Very bad service',
        ]);
    }
}
