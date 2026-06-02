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

class JobFlowDistributionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $service;

    protected $areaA;

    protected $baseLat = 10.762622;

    protected $baseLng = 106.660172;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceCategory::factory()->create(['status' => 'active']);
        $this->areaA = Area::factory()->create(['status' => 'active', 'name' => 'Khu vuc Trung Tam']);

        // Setup Customer
        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);
        CustomerProfile::create([
            'user_id' => $this->customer->id,
            'phone' => '0901234567',
            'area_id' => $this->areaA->id,
        ]);

        // Required for distribution configuration
        Configuration::updateOrCreate(
            ['key' => 'job_assignment_config'],
            [
                'value' => json_encode([
                    'scan_radius' => 10,   // km
                    'max_workers_per_job' => 5,
                    'rating_weight' => 0.5,
                    'distance_weight' => 0.3,
                    'response_rate_weight' => 0.2,
                ]),
            ]
        );
    }

    private function createWorker($latOffset, $lngOffset, $rating = 5)
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
            'avg_rating' => $rating,
            'total_completed_jobs' => 10,
            'latitude' => $this->baseLat + $latOffset,
            'longitude' => $this->baseLng + $lngOffset,
        ]);

        WorkerService::create([
            'worker_profile_id' => $workerProfile->id,
            'service_category_id' => $this->service->id,
        ]);

        WorkerArea::create([
            'worker_profile_id' => $workerProfile->id,
            'area_id' => $this->areaA->id,
        ]);

        return $worker;
    }

    public function test_job_is_distributed_to_max_5_workers()
    {
        // Create 6 workers at exactly the same location (all eligible, all inside radius)
        $workers = [];
        for ($i = 0; $i < 6; $i++) {
            $workers[] = $this->createWorker(0.01, 0.01, 5);
        }

        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Limit 5 workers',
            'area_id' => $this->areaA->id,
            'address' => 'Test Location',
            'latitude' => $this->baseLat,
            'longitude' => $this->baseLng,
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $createRes = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData);
        $createRes->assertStatus(201);
        $jobId = $createRes->json('data.id');

        // Verify exactly 5 workers were invited (not 6)
        $invitedCount = \DB::table('t_job_invited_workers')->where('job_id', $jobId)->count();
        $this->assertEquals(5, $invitedCount);

        // Verify that exactly 1 worker was NOT invited despite being in radius
        $invitedWorkerIds = \DB::table('t_job_invited_workers')
            ->where('job_id', $jobId)
            ->pluck('worker_id')
            ->toArray();
        $uninvitedWorkers = collect($workers)->filter(fn ($w) => ! in_array($w->id, $invitedWorkerIds));
        $this->assertCount(1, $uninvitedWorkers, 'Exactly 1 worker in radius should be excluded due to max limit');
    }

    public function test_6th_worker_inside_radius_cannot_see_distributed_job()
    {
        // Create 6 workers inside radius, only 5 get invited
        $workers = [];
        for ($i = 0; $i < 6; $i++) {
            $workers[] = $this->createWorker(0.01, 0.01, 5);
        }

        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Worker inside radius excluded by limit',
            'area_id' => $this->areaA->id,
            'address' => 'Test Location',
            'latitude' => $this->baseLat,
            'longitude' => $this->baseLng,
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $createRes = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData);
        $jobId = $createRes->json('data.id');

        // Find the uninvited worker
        $invitedWorkerIds = \DB::table('t_job_invited_workers')
            ->where('job_id', $jobId)
            ->pluck('worker_id')
            ->toArray();
        $uninvitedWorker = collect($workers)->first(fn ($w) => ! in_array($w->id, $invitedWorkerIds));

        // The 6th worker (in radius but not invited) should NOT see the job
        $availableJobs = $this->actingAs($uninvitedWorker, 'api')->getJson('/api/worker/jobs/available');
        $availableJobs->assertStatus(200);

        $jobIds = collect($availableJobs->json('data.data'))->pluck('id');
        $this->assertFalse(
            $jobIds->contains($jobId),
            '6th worker inside radius should NOT see the job because it already has invited workers'
        );
    }

    public function test_uninvited_worker_cannot_see_distributed_job()
    {
        // 5 workers very close (invited)
        for ($i = 0; $i < 5; $i++) {
            $this->createWorker(0.01, 0.01, 5);
        }

        // 1 worker a bit further (not invited because top 5 took it)
        $workerFar = $this->createWorker(0.05, 0.05, 1);

        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Uninvited visibility',
            'area_id' => $this->areaA->id,
            'address' => 'Test Location',
            'latitude' => $this->baseLat,
            'longitude' => $this->baseLng,
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $createRes = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData);
        $jobId = $createRes->json('data.id');

        // Check if far worker sees the job (should not because they are not invited)
        $availableJobs = $this->actingAs($workerFar, 'api')->getJson('/api/worker/jobs/available');
        $availableJobs->assertStatus(200);

        $jobIds = collect($availableJobs->json('data.data'))->pluck('id');
        $this->assertFalse($jobIds->contains($jobId), 'Uninvited worker should not see a job that has invited workers');
    }

    public function test_job_without_invited_workers_is_visible_to_all()
    {
        // Create job first, no workers exist yet
        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'This is an Open Job',
            'area_id' => $this->areaA->id,
            'address' => 'Test Location',
            'latitude' => $this->baseLat,
            'longitude' => $this->baseLng,
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $createRes = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData);
        $jobId = $createRes->json('data.id');

        // Create a worker AFTER job distribution.
        // This worker was not invited during initial creation.
        $newWorker = $this->createWorker(0.01, 0.01);

        // Worker should see it, because there are NO invited workers at all, making it an OPEN job.
        $availableJobs = $this->actingAs($newWorker, 'api')->getJson('/api/worker/jobs/available');
        $availableJobs->assertStatus(200);

        $jobIds = collect($availableJobs->json('data.data'))->pluck('id');
        $this->assertTrue($jobIds->contains($jobId), 'Worker should see OPEN job (no invites)');
    }

    public function test_prioritize_workers_within_geo_distance()
    {
        // 1 degree latitude ~ 111 km. 10km scan radius ~ 0.09 degrees.
        // Create 5 workers inside the 10km radius
        $workersInside = [];
        for ($i = 0; $i < 5; $i++) {
            $workersInside[] = $this->createWorker(0.01 + ($i * 0.005), 0.00);
        }

        // Worker 6: 15km away (outside 10km limit)
        $workerOutside = $this->createWorker(0.15, 0.00);

        $jobData = [
            'service_id' => $this->service->id,
            'description' => 'Distance prioritization',
            'area_id' => $this->areaA->id,
            'address' => 'Test Location',
            'latitude' => $this->baseLat,
            'longitude' => $this->baseLng,
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [],
        ];

        $createRes = $this->actingAs($this->customer, 'api')->postJson('/api/customer/jobs', $jobData);
        $jobId = $createRes->json('data.id');

        // Verify invitations
        foreach ($workersInside as $worker) {
            $this->assertDatabaseHas('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $worker->id]);
        }
        $this->assertDatabaseMissing('t_job_invited_workers', ['job_id' => $jobId, 'worker_id' => $workerOutside->id]);
    }
}
