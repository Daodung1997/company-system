<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CustomerJobListTabTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Customer
        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        // Setup Worker (for assigned jobs)
        $this->worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);
    }

    private function createJob($status)
    {
        return Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => in_array($status, [JobStatusConst::WAITING_FOR_QUOTATION, JobStatusConst::QUOTED]) ? null : $this->worker->id,
            'status' => $status,
        ]);
    }

    public function test_customer_list_jobs_requesting_tab()
    {
        // Requesting
        $job1 = $this->createJob(JobStatusConst::WAITING_FOR_QUOTATION);
        $job2 = $this->createJob(JobStatusConst::QUOTED);

        // In Progress (Should not appear)
        $this->createJob(JobStatusConst::IN_PROGRESS);

        // Completed (Should not appear)
        $this->createJob(JobStatusConst::COMPLETED);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/customer/jobs?tab=requesting');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.data');

        $ids = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertTrue(in_array($job1->id, $ids));
        $this->assertTrue(in_array($job2->id, $ids));
    }

    public function test_customer_list_jobs_in_progress_tab()
    {
        // Requesting (Should not appear)
        $this->createJob(JobStatusConst::WAITING_FOR_QUOTATION);

        // In Progress
        $job1 = $this->createJob(JobStatusConst::PENDING_PAYMENT);
        $job2 = $this->createJob(JobStatusConst::PAID);
        $job3 = $this->createJob(JobStatusConst::IN_PROGRESS);
        $job4 = $this->createJob(JobStatusConst::COMPLAINT);

        // Completed (Should not appear)
        $this->createJob(JobStatusConst::COMPLETED);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/customer/jobs?tab=in_progress');

        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data.data');

        $ids = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertTrue(in_array($job1->id, $ids));
        $this->assertTrue(in_array($job4->id, $ids));
    }

    public function test_customer_list_jobs_completed_tab()
    {
        // Requesting (Should not appear)
        $this->createJob(JobStatusConst::WAITING_FOR_QUOTATION);

        // In Progress (Should not appear)
        $this->createJob(JobStatusConst::IN_PROGRESS);

        // Completed
        $job1 = $this->createJob(JobStatusConst::COMPLETED);
        $job2 = $this->createJob(JobStatusConst::REFUNDED);
        $job3 = $this->createJob(JobStatusConst::CANCELLED);
        $job4 = $this->createJob(JobStatusConst::EXPIRED);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/customer/jobs?tab=completed');

        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data.data');

        $ids = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertTrue(in_array($job1->id, $ids));
        $this->assertTrue(in_array($job4->id, $ids));
    }

    public function test_customer_list_jobs_without_tab()
    {
        // Create 1 of each type
        $job1 = $this->createJob(JobStatusConst::WAITING_FOR_QUOTATION);
        $job2 = $this->createJob(JobStatusConst::IN_PROGRESS);
        $job3 = $this->createJob(JobStatusConst::COMPLETED);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/customer/jobs');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.data');
    }

    public function test_customer_list_jobs_active_tab()
    {
        // Active Statuses
        $job1 = $this->createJob(JobStatusConst::WAITING_FOR_QUOTATION);
        $job2 = $this->createJob(JobStatusConst::QUOTED);
        $job3 = $this->createJob(JobStatusConst::PENDING_PAYMENT);
        $job4 = $this->createJob(JobStatusConst::PAID);
        $job5 = $this->createJob(JobStatusConst::IN_PROGRESS);

        // Completed/History Statuses (Should not appear)
        $this->createJob(JobStatusConst::COMPLETED);
        $this->createJob(JobStatusConst::EXPIRED);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/customer/jobs?tab=active');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data.data');

        $ids = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertTrue(in_array($job1->id, $ids));
        $this->assertTrue(in_array($job5->id, $ids));
    }

    public function test_customer_list_jobs_history_tab()
    {
        // Active Statuses (Should not appear)
        $this->createJob(JobStatusConst::WAITING_FOR_QUOTATION);
        $this->createJob(JobStatusConst::IN_PROGRESS);

        // History Statuses
        $job1 = $this->createJob(JobStatusConst::COMPLETED);
        $job2 = $this->createJob(JobStatusConst::COMPLAINT);
        $job3 = $this->createJob(JobStatusConst::REFUNDED);
        $job4 = $this->createJob(JobStatusConst::CANCELLED);
        $job5 = $this->createJob(JobStatusConst::EXPIRED);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/customer/jobs?tab=history');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data.data');

        $ids = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertTrue(in_array($job1->id, $ids));
        $this->assertTrue(in_array($job2->id, $ids));
        $this->assertTrue(in_array($job5->id, $ids));
    }
}
