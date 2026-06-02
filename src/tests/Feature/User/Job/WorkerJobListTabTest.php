<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WorkerJobListTabTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $worker;

    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);
    }

    private function createAssignedJob($status)
    {
        return Job::factory()->create([
            'worker_id' => $this->worker->id,
            'customer_id' => $this->customer->id,
            'status' => $status,
        ]);
    }

    private function createQuotedJob($quotationStatus = QuotationStatusConst::PENDING)
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::QUOTED,
        ]);

        Quotation::factory()->create([
            'job_id' => $job->id,
            'worker_id' => $this->worker->id,
            'status' => $quotationStatus,
        ]);

        return $job;
    }

    public function test_worker_list_jobs_in_progress_tab()
    {
        // Jobs that should appear in in_progress tab
        $jobQuoted = $this->createQuotedJob();
        $jobAssigned1 = $this->createAssignedJob(JobStatusConst::PENDING_PAYMENT);
        $jobAssigned2 = $this->createAssignedJob(JobStatusConst::PAID);
        $jobAssigned3 = $this->createAssignedJob(JobStatusConst::IN_PROGRESS);
        $jobAssigned4 = $this->createAssignedJob(JobStatusConst::COMPLAINT);

        // Jobs that should NOT appear
        $this->createAssignedJob(JobStatusConst::COMPLETED);
        $this->createAssignedJob(JobStatusConst::CANCELLED);
        Job::factory()->create(['status' => JobStatusConst::WAITING_FOR_QUOTATION]); // not worker's job

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs?tab=in_progress');

        $response->assertStatus(200);

        $ids = collect($response->json('data.data'))->pluck('id')->toArray();
        $response->assertJsonCount(5, 'data.data');

        $this->assertTrue(in_array($jobQuoted->id, $ids));
        $this->assertTrue(in_array($jobAssigned1->id, $ids));
        $this->assertTrue(in_array($jobAssigned4->id, $ids));
    }

    public function test_worker_list_jobs_completed_tab()
    {
        // Jobs that should appear in completed tab
        $jobQuotedRejected = $this->createQuotedJob(QuotationStatusConst::REJECTED);
        $jobAssigned1 = $this->createAssignedJob(JobStatusConst::COMPLETED);
        $jobAssigned2 = $this->createAssignedJob(JobStatusConst::REFUNDED);
        $jobAssigned3 = $this->createAssignedJob(JobStatusConst::CANCELLED);
        $jobAssigned4 = $this->createAssignedJob(JobStatusConst::EXPIRED);

        // Jobs that should NOT appear
        $this->createQuotedJob();
        $this->createAssignedJob(JobStatusConst::IN_PROGRESS);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs?tab=completed');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data.data');

        $ids = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertTrue(in_array($jobQuotedRejected->id, $ids));
        $this->assertTrue(in_array($jobAssigned1->id, $ids));
        $this->assertTrue(in_array($jobAssigned4->id, $ids));
    }

    public function test_worker_list_jobs_without_tab()
    {
        // Create 1 of each type
        $job1 = $this->createQuotedJob();
        $job2 = $this->createAssignedJob(JobStatusConst::IN_PROGRESS);
        $job3 = $this->createAssignedJob(JobStatusConst::COMPLETED);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs');

        $response->assertStatus(200);

        // When no tab is passed, all worker's jobs (quoted or assigned) should be loaded
        $response->assertJsonCount(3, 'data.data');
    }

    public function test_worker_list_jobs_with_type_quoted()
    {
        $jobQuoted = $this->createQuotedJob();
        $this->createAssignedJob(JobStatusConst::IN_PROGRESS);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs?type=quoted');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $this->assertEquals($jobQuoted->id, $response->json('data.data.0.id'));
    }

    public function test_worker_list_jobs_with_type_assigned()
    {
        $this->createQuotedJob();
        $jobAssigned = $this->createAssignedJob(JobStatusConst::IN_PROGRESS);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/jobs?type=assigned');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $this->assertEquals($jobAssigned->id, $response->json('data.data.0.id'));
    }
}
