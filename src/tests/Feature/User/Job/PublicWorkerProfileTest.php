<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Models\Job;
use App\Models\JobInvitedWorker;
use App\Models\Quotation;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWorkerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;

    protected $worker;

    protected $workerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => CommonRolesConst::CUSTOMER]);
        $this->worker = User::factory()->create(['role' => CommonRolesConst::WORKER]);
        $this->workerProfile = WorkerProfile::factory()->create([
            'user_id' => $this->worker->id,
            'experience_years' => 3,
            'skill_description' => 'Expert plumber',
            'avg_rating' => 4.5,
            'total_completed_jobs' => 10,
        ]);
    }

    public function test_customer_can_view_worker_who_quoted()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        Quotation::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'status' => QuotationStatusConst::PENDING,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/workers/{$this->worker->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'code',
            'data' => ['id', 'name', 'avatar_code', 'experience_years', 'avg_rating', 'total_completed_jobs'],
        ]);
        // Must NOT contain sensitive fields
        $response->assertJsonMissing(['phone', 'email', 'id_card_number', 'latitude', 'longitude', 'price_type', 'price_note', 'price_options']);
    }

    public function test_customer_can_view_assigned_worker()
    {
        Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/workers/{$this->worker->id}");

        $response->assertStatus(200);
    }

    public function test_customer_can_view_invited_worker()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        JobInvitedWorker::create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/workers/{$this->worker->id}");

        $response->assertStatus(200);
    }

    public function test_customer_cannot_view_unrelated_worker()
    {
        // No jobs connecting customer and worker
        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/workers/{$this->worker->id}");

        $response->assertStatus(403);
    }

    public function test_returns_404_for_nonexistent_profile()
    {
        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/customer/workers/99999');

        $response->assertStatus(404);
    }
}
