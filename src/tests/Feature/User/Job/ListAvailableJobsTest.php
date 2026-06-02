<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonStatusConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Models\Area;
use App\Models\Configuration;
use App\Models\CustomerProfile;
use App\Models\Job;
use App\Models\JobInvitedWorker;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerArea;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListAvailableJobsTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;

    protected $worker;

    protected $service;

    protected $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = ServiceCategory::factory()->create(['status' => 'active']);
        $this->area = Area::factory()->create(['status' => 'active']);

        // Create customer with profile
        $this->customer = User::factory()->create([
            'role' => 'customer',
            'status' => CommonStatusConst::ACTIVE,
        ]);
        CustomerProfile::create([
            'user_id' => $this->customer->id,
            'phone' => '0901234567',
            'area_id' => $this->area->id,
        ]);

        // Create worker with profile, services, areas
        $this->worker = $this->createWorker();

        // Set default config
        Configuration::create([
            'key' => 'job_assignment_config',
            'value' => json_encode([
                'scan_radius' => 10,
                'max_workers_per_job' => 5,
                'rating_weight' => 0.5,
                'distance_weight' => 0.3,
                'response_rate_weight' => 0.2,
            ]),
        ]);
    }

    private function createWorker(): User
    {
        $worker = User::factory()->create([
            'role' => 'worker',
            'status' => CommonStatusConst::ACTIVE,
        ]);

        WorkerProfile::factory()->create([
            'user_id' => $worker->id,
            'availability' => true,
            'activity_status' => 'active',
            'profile_status' => 'approved',
            'avg_rating' => 4.0,
            'total_completed_jobs' => 10,
        ]);

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
     * Helper: Create a job and optionally invite the worker.
     */
    private function createJob(bool $inviteWorker = false): Job
    {
        $job = Job::create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'area_id' => $this->area->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
            'description' => 'Test job',
            'address' => '123 Test St',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => '08:00-10:00',
        ]);

        if ($inviteWorker) {
            JobInvitedWorker::create([
                'job_id' => $job->id,
                'worker_id' => $this->worker->id,
                'status' => 'assigned',
            ]);
        }

        return $job;
    }

    // =========================================================================
    // Test: is_invited flag
    // =========================================================================

    public function test_available_jobs_returns_is_invited_true_for_invited_jobs()
    {
        $job = $this->createJob(inviteWorker: true);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs/available');

        $response->assertStatus(200);
        $data = collect($response->json('data.data'));
        $jobData = $data->firstWhere('id', $job->id);

        $this->assertNotNull($jobData, 'Invited job should be visible');
        $this->assertTrue($jobData['is_invited']);
    }

    public function test_available_jobs_returns_is_invited_false_for_open_jobs()
    {
        $job = $this->createJob(inviteWorker: false);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs/available');

        $response->assertStatus(200);
        $data = collect($response->json('data.data'));
        $jobData = $data->firstWhere('id', $job->id);

        $this->assertNotNull($jobData, 'Open job should be visible');
        $this->assertFalse($jobData['is_invited']);
    }

    // =========================================================================
    // Test: type filter
    // =========================================================================

    public function test_filter_type_invited_returns_only_invited_jobs()
    {
        $invitedJob = $this->createJob(inviteWorker: true);
        $openJob = $this->createJob(inviteWorker: false);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs/available?type=invited');

        $response->assertStatus(200);
        $data = collect($response->json('data.data'));

        $this->assertTrue($data->contains('id', $invitedJob->id), 'Should contain invited job');
        $this->assertFalse($data->contains('id', $openJob->id), 'Should NOT contain open job');
    }

    public function test_filter_type_open_returns_only_open_jobs()
    {
        $invitedJob = $this->createJob(inviteWorker: true);
        $openJob = $this->createJob(inviteWorker: false);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs/available?type=open');

        $response->assertStatus(200);
        $data = collect($response->json('data.data'));

        $this->assertFalse($data->contains('id', $invitedJob->id), 'Should NOT contain invited job');
        $this->assertTrue($data->contains('id', $openJob->id), 'Should contain open job');
    }

    public function test_filter_type_all_returns_both_open_and_invited_jobs()
    {
        $invitedJob = $this->createJob(inviteWorker: true);
        $openJob = $this->createJob(inviteWorker: false);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs/available?type=all');

        $response->assertStatus(200);
        $data = collect($response->json('data.data'));

        $this->assertTrue($data->contains('id', $invitedJob->id));
        $this->assertTrue($data->contains('id', $openJob->id));
    }

    public function test_default_no_type_filter_returns_both()
    {
        $invitedJob = $this->createJob(inviteWorker: true);
        $openJob = $this->createJob(inviteWorker: false);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs/available');

        $response->assertStatus(200);
        $data = collect($response->json('data.data'));

        $this->assertTrue($data->contains('id', $invitedJob->id));
        $this->assertTrue($data->contains('id', $openJob->id));
    }
}
