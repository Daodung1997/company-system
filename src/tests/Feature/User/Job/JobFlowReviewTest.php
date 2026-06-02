<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Job\JobTimeSlotConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Area;
use App\Models\Configuration;
use App\Models\CustomerProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerArea;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class JobFlowReviewTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $workerA;

    protected $service;

    protected $areaA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceCategory::factory()->create(['status' => 'active']);
        $this->areaA = Area::factory()->create(['status' => 'active', 'name' => 'Khu vuc A']);

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
            'avg_rating' => 0, // start with 0 for precise rating testing
            'total_completed_jobs' => 0,
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

    private function createAndCompleteJob($customer, $worker)
    {
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Job Needs Fixing',
            'area_id' => $this->areaA->id,
            'address' => 'Address A',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $createRes = $this->actingAs($customer, 'api')->postJson('/api/customer/jobs', $jobData);
        $createRes->assertStatus(201);
        $jobId = $createRes->json('data.id');

        $quote = $this->actingAs($worker, 'api')->postJson("/api/worker/jobs/{$jobId}/quotation", [
            'price' => 500000,
            'estimated_duration' => '1 hour',
        ]);
        $quote->assertStatus(201);
        $quotationId = $quote->json('data.id');

        $acceptRes = $this->actingAs($customer, 'api')
            ->postJson("/api/customer/jobs/{$jobId}/quotations/{$quotationId}/accept");
        $acceptRes->assertStatus(200);

        $payRes = $this->actingAs($customer, 'api')
            ->postJson("/api/customer/jobs/{$jobId}/payment/cash");
        $payRes->assertStatus(200);

        $startByWorker = $this->actingAs($worker, 'api')->postJson("/api/worker/jobs/{$jobId}/start");
        $startByWorker->assertStatus(200);

        $completeByWorker = $this->actingAs($worker, 'api')->postJson("/api/worker/jobs/{$jobId}/complete");
        $completeByWorker->assertStatus(200);

        return $jobId;
    }

    public function test_customer_can_review_completed_job_and_worker_rating_updates()
    {
        $jobId = $this->createAndCompleteJob($this->customer, $this->workerA);

        $reviewRes = $this->actingAs($this->customer, 'api')->postJson('/api/user/reviews', [
            'job_id' => $jobId,
            'rating' => 5,
            'comment' => 'Very good worker',
        ]);

        $reviewRes->assertStatus(200);

        $this->assertDatabaseHas('t_reviews', [
            'job_id' => $jobId,
            'reviewer_id' => $this->customer->id,
            'target_id' => $this->workerA->id,
            'rating' => 5,
            'comment' => 'Very good worker',
        ]);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->workerA->id,
            'avg_rating' => 5.0,
        ]);
    }

    public function test_customer_cannot_review_uncompleted_job()
    {
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Uncompleted Job Review',
            'area_id' => $this->areaA->id,
            'address' => 'Address A',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $jobId = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData)->json('data.id');

        $reviewRes = $this->actingAs($this->customer, 'api')->postJson('/api/user/reviews', [
            'job_id' => $jobId,
            'rating' => 5,
            'comment' => 'Very good worker',
        ]);

        // Should be 422 because job is not completed and rule exists will fail
        $reviewRes->assertStatus(422);
    }

    public function test_customer_cannot_review_twice()
    {
        $jobId = $this->createAndCompleteJob($this->customer, $this->workerA);

        $this->actingAs($this->customer, 'api')->postJson('/api/user/reviews', [
            'job_id' => $jobId,
            'rating' => 5,
            'comment' => 'First review',
        ])->assertStatus(200);

        $reviewRes2 = $this->actingAs($this->customer, 'api')->postJson('/api/user/reviews', [
            'job_id' => $jobId,
            'rating' => 4,
            'comment' => 'Second review',
        ]);

        $reviewRes2->assertStatus(422);
    }

    public function test_worker_rating_calculation_with_multiple_reviews()
    {
        // Job 1
        $jobId1 = $this->createAndCompleteJob($this->customer, $this->workerA);
        $this->actingAs($this->customer, 'api')->postJson('/api/user/reviews', [
            'job_id' => $jobId1,
            'rating' => 5,
            'comment' => 'First review',
        ])->assertStatus(200);

        // We need a second customer to create another job, or the same customer can create Job 2
        $jobId2 = $this->createAndCompleteJob($this->customer, $this->workerA);
        $this->actingAs($this->customer, 'api')->postJson('/api/user/reviews', [
            'job_id' => $jobId2,
            'rating' => 3,
            'comment' => 'Second review',
        ])->assertStatus(200);

        // Average should be (5 + 3) / 2 = 4
        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->workerA->id,
            'avg_rating' => 4.0,
        ]);
    }
}
