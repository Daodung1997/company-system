<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonStatusConst;
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
use Tests\TestCase;

class JobDistributionTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;

    protected $service;

    protected $area;

    // Ho Chi Minh City center coordinates for testing
    const JOB_LAT = 10.7769;

    const JOB_LNG = 106.7009;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceCategory::factory()->create(['status' => 'active']);
        $this->area = Area::factory()->create(['status' => 'active']);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'status' => CommonStatusConst::ACTIVE,
        ]);

        // Create customer profile with required fields
        $this->customer->setRelation('customerProfile', CustomerProfile::create([
            'user_id' => $this->customer->id,
            'phone' => '0901234567',
            'address' => '123 Test St',
            'area_id' => $this->area->id,
        ]));

        // Set default config
        Configuration::create([
            'key' => 'job_assignment_config',
            'value' => json_encode([
                'scan_radius' => 10,
                'max_workers_per_job' => 3,
                'rating_weight' => 0.5,
                'distance_weight' => 0.3,
                'response_rate_weight' => 0.2,
            ]),
        ]);
    }

    /**
     * Helper: Create a worker with profile, service, area, and optional geo coordinates.
     */
    private function createWorker(array $profileOverrides = [], ?float $lat = null, ?float $lng = null): User
    {
        $worker = User::factory()->create([
            'role' => 'worker',
            'status' => \App\Constants\Master\Models\User\UserStatusConst::ACTIVE,
        ]);

        $profileData = array_merge([
            'user_id' => $worker->id,
            'availability' => true,
            'activity_status' => 'active',
            'profile_status' => 'approved',
            'avg_rating' => 4.0,
            'total_completed_jobs' => 10,
            'latitude' => $lat,
            'longitude' => $lng,
        ], $profileOverrides);

        WorkerProfile::factory()->create($profileData);

        WorkerService::create([
            'worker_profile_id' => $worker->workerProfile->id,
            'service_category_id' => $this->service->id,
        ]);

        WorkerArea::create([
            'worker_profile_id' => $worker->workerProfile->id,
            'area_id' => $this->area->id,
        ]);

        return $worker;
    }

    /**
     * Helper: Create a job via API.
     */
    private function createJobRequest(array $overrides = [])
    {
        $payload = array_merge([
            'service_id' => $this->service->id,
            'area_id' => $this->area->id,
            'description' => 'Test Job Description for distribution testing',
            'address' => '123 Test St',
            'latitude' => self::JOB_LAT,
            'longitude' => self::JOB_LNG,
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => '08:00-10:00',
        ], $overrides);

        return $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $payload);
    }

    // =========================================================================
    // Test: Geo-distance based distribution
    // =========================================================================

    public function test_geo_distribution_selects_nearest_workers()
    {
        // Worker A: 2km away (closest)
        $workerA = $this->createWorker(['avg_rating' => 3.0], 10.7950, 106.7009);
        // Worker B: 5km away
        $workerB = $this->createWorker(['avg_rating' => 5.0], 10.8200, 106.7009);
        // Worker C: 1km away (very close)
        $workerC = $this->createWorker(['avg_rating' => 2.0], 10.7859, 106.7009);
        // Worker D: 15km away (outside scan_radius of 10km)
        $workerD = $this->createWorker(['avg_rating' => 5.0], 10.9100, 106.7009);

        $response = $this->createJobRequest();

        $response->assertStatus(201);
        $jobId = $response->json('data.id');

        // Workers A, B, C should be invited (within 10km), sorted by distance
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $workerA->id]);
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $workerB->id]);
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $workerC->id]);

        // Worker D should NOT be invited (outside 10km radius)
        $this->assertDatabaseMissing('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $workerD->id]);
    }

    // =========================================================================
    // Test: Area fallback when job has no lat/lng
    // =========================================================================

    public function test_area_fallback_when_no_geo_coordinates()
    {
        // Workers with area match but no lat/lng
        $worker1 = $this->createWorker(['avg_rating' => 5.0]);
        $worker2 = $this->createWorker(['avg_rating' => 4.0]);
        $worker3 = $this->createWorker(['avg_rating' => 3.0]);

        // Create job WITHOUT lat/lng
        $response = $this->createJobRequest([
            'latitude' => null,
            'longitude' => null,
        ]);

        $response->assertStatus(201);
        $jobId = $response->json('data.id');

        // Should fall back to area-based matching
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $worker1->id]);
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $worker2->id]);
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $worker3->id]);
    }

    // =========================================================================
    // Test: Mixed — geo workers + area-only workers
    // =========================================================================

    public function test_mixed_geo_and_area_fallback()
    {
        // 2 workers with geo (close)
        $geoWorker1 = $this->createWorker(['avg_rating' => 4.0], 10.7800, 106.7050);
        $geoWorker2 = $this->createWorker(['avg_rating' => 3.0], 10.7900, 106.7100);

        // 2 workers without geo but matching area
        $areaWorker1 = $this->createWorker(['avg_rating' => 5.0]);
        $areaWorker2 = $this->createWorker(['avg_rating' => 2.0]);

        $response = $this->createJobRequest();
        $response->assertStatus(201);
        $jobId = $response->json('data.id');

        // max_workers_per_job = 3: 2 geo workers + 1 area fallback (highest rating)
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $geoWorker1->id]);
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $geoWorker2->id]);
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $areaWorker1->id]);

        // 4th worker should NOT be invited (max 3)
        $this->assertDatabaseMissing('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $areaWorker2->id]);
    }

    // =========================================================================
    // Test: Profile incomplete → 403
    // =========================================================================

    public function test_create_job_fails_without_complete_profile()
    {
        // Create customer without profile
        $incompleteCustomer = User::factory()->create([
            'role' => 'customer',
            'status' => CommonStatusConst::ACTIVE,
        ]);

        // Profile with missing phone
        $incompleteCustomer->setRelation('customerProfile', CustomerProfile::create([
            'user_id' => $incompleteCustomer->id,
            'phone' => null,
            'address' => null,
        ]));

        $response = $this->actingAs($incompleteCustomer, 'api')
            ->postJson('/api/customer/jobs', [
                'service_id' => $this->service->id,
                'area_id' => $this->area->id,
                'description' => 'Test Job without profile',
                'address' => '123 Test St',
                'scheduled_date' => now()->addDay()->format('Y-m-d'),
                'time_slot' => '08:00-10:00',
            ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error_code' => 'JOB_001']);
    }

    // =========================================================================
    // Test: Rating-based selection with same distance
    // =========================================================================

    public function test_geo_distribution_uses_rating_as_tiebreaker()
    {
        // All workers at same distance (~2km)
        $workerHigh = $this->createWorker(['avg_rating' => 5.0, 'total_completed_jobs' => 50], 10.7950, 106.7009);
        $workerMid = $this->createWorker(['avg_rating' => 3.0, 'total_completed_jobs' => 30], 10.7950, 106.7010);
        $workerLow = $this->createWorker(['avg_rating' => 1.0, 'total_completed_jobs' => 5], 10.7950, 106.7011);
        $workerExcluded = $this->createWorker(['avg_rating' => 4.0, 'total_completed_jobs' => 40], 10.7950, 106.7012);

        $response = $this->createJobRequest();
        $response->assertStatus(201);
        $jobId = $response->json('data.id');

        // Top 3 should be invited (distance is same, so sorted by rating)
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $workerHigh->id]);
        $this->assertDatabaseMissing('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $workerExcluded->id]);
        $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $workerMid->id]);
    }
}
