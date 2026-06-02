<?php

namespace Tests\Feature\Admin;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Models\Complaint;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class ComplaintResolutionTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase, WithFaker;

    protected $admin;

    protected $customer;

    protected $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminWithAllPermissions();

        $this->customer = User::factory()->create(['role' => CommonRolesConst::CUSTOMER]);
        $this->worker = User::factory()->create(['role' => CommonRolesConst::WORKER]);
    }

    public function test_admin_can_resolve_job_as_complete()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::COMPLAINT,
            'quotation_price' => 100000,
        ]);

        Complaint::create([
            'job_id' => $job->id,
            'description' => 'Test Complaint',
            'status' => 'pending',
            'created_by' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/jobs/{$job->id}/resolve/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', JobStatusConst::COMPLETED);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::COMPLETED,
        ]);

        $this->assertDatabaseHas('t_complaints', [
            'job_id' => $job->id,
            'status' => 'resolved',
        ]);

        $this->assertDatabaseHas('t_wallet_transactions', [
            'worker_id' => $this->worker->id,
            'amount' => 100000,
            'type' => \App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst::EARNING,
            'job_id' => $job->id,
        ]);
    }

    public function test_admin_can_resolve_job_as_refund()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::COMPLAINT,
        ]);

        Complaint::create([
            'job_id' => $job->id,
            'description' => 'Test Complaint',
            'status' => 'pending',
            'created_by' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/jobs/{$job->id}/resolve/refund");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', JobStatusConst::CANCELLED);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::CANCELLED,
        ]);

        $this->assertDatabaseHas('t_complaints', [
            'job_id' => $job->id,
            'status' => 'resolved',
        ]);
    }
}
