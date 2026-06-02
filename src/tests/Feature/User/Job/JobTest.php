<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Job\JobTimeSlotConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Area;
use App\Models\Job;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

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

        \Illuminate\Support\Facades\DB::table('m_customer_profiles')
            ->updateOrInsert(
                ['user_id' => $this->customer->id],
                [
                    'phone' => '0987654321',
                    'address' => '123 Test Street',
                    'area_id' => $this->area->id,
                ]
            );

        $this->customer->load('customerProfile');

        $this->customer->load('customerProfile');
    }

    public function test_customer_can_create_job()
    {
        $image = \App\Models\Image::factory()->create([
            'extension' => 'jpg',
            'path_image_original' => 'images/job_image.jpg',
            'disk' => 'public',
        ]);

        $data = [
            'service_id' => $this->service->id,
            'description' => 'Need help with cleaning.',
            'area_id' => $this->area->id,
            'address' => '123 Test Street',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'media_codes' => [$image->code],
        ];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', JobStatusConst::WAITING_FOR_QUOTATION)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'service' => ['id', 'name'],
                    'description',
                    'media',
                    'address',
                    'area' => ['id', 'name'],
                    'scheduled_date',
                    'time_slot',
                    'status',
                    'created_at',
                    'worker',
                ],
            ]);

        $this->assertDatabaseHas('t_jobs', [
            'customer_id' => $this->customer->id,
            'description' => 'Need help with cleaning.',
        ]);

        $jobId = $response->json('data.id');
        $this->assertDatabaseHas('t_job_media', [
            'job_id' => $jobId,
            'type' => 'image',
        ]);

        // Assert file exists
        // Note: Repository stores with 'public' disk
        // $path = ... logic in service: "jobs/{$job->id}"
        // Check manually or assume repository logic correct via database check above.
    }

    public function test_customer_cannot_create_job_with_invalid_data()
    {
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', [
                'description' => 'Short', // too short
            ]);

        $response->assertStatus(422);
    }

    public function test_customer_can_view_own_jobs()
    {
        Job::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/customer/jobs');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data')
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'code',
                            'service',
                            'description',
                            'status',
                        ],
                    ],
                ],
            ]);
    }

    public function test_customer_can_view_job_detail()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $job->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'service' => ['id', 'name'],
                    'description',
                    'media',
                    'address',
                    'area' => ['id', 'name'],
                    'scheduled_date',
                    'time_slot',
                    'status',
                    'worker',
                ],
            ]);
    }

    public function test_customer_cannot_view_others_job()
    {
        $otherUser = User::factory()->create();
        $job = Job::factory()->create([
            'customer_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/jobs/{$job->id}");

        $response->assertStatus(403);
    }

    public function test_customer_can_cancel_job()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::CANCELLED,
            'cancelled_reason' => 'Changed my mind',
        ]);
    }

    public function test_customer_cannot_cancel_processed_job()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/cancel", [
                'reason' => 'Too late',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_STATUS);
    }

    public function test_customer_can_review_worker()
    {
        $worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        \App\Models\WorkerProfile::factory()->create([
            'user_id' => $worker->id,
        ]);

        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $worker->id,
            'status' => JobStatusConst::COMPLETED,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/review", [
                'rating' => 5,
                'comment' => 'Great worker',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success', true);

        $this->assertDatabaseHas('t_reviews', [
            'job_id' => $job->id,
            'reviewer_id' => $this->customer->id,
            'target_id' => $worker->id,
            'rating' => 5,
            'comment' => 'Great worker',
        ]);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $worker->id,
            'avg_rating' => 5.0,
        ]);
    }

    public function test_guest_cannot_access_job_endpoints()
    {
        // Try create
        $this->postJson('/api/customer/jobs', [])
            ->assertStatus(401);

        // Try list
        $this->getJson('/api/customer/jobs')
            ->assertStatus(401);

        // Try view detail
        $job = Job::factory()->create();
        $this->getJson("/api/customer/jobs/{$job->id}")
            ->assertStatus(401);

        // Try cancel
        $this->postJson("/api/customer/jobs/{$job->id}/cancel", [])
            ->assertStatus(401);
    }
}
