<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SubmitComplaintTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $this->worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);
    }

    public function test_customer_can_submit_complaint()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::COMPLETED,
        ]);

        $data = [
            'content' => 'Worker did not finish the job properly.',
        ];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/complaint", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', \App\Constants\Master\Models\Complaint\ComplaintStatusConst::PENDING);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::COMPLAINT,
        ]);

        $this->assertDatabaseHas('t_complaints', [
            'job_id' => $job->id,
            'description' => 'Worker did not finish the job properly.',
        ]);
    }

    public function test_customer_cannot_submit_complaint_wrong_status()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/complaint", ['content' => 'This is a test content that is long enough.']);

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_STATUS);
    }
}
