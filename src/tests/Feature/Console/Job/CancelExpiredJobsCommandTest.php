<?php

namespace Tests\Feature\Console\Job;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Models\Area;
use App\Models\Job;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelExpiredJobsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $service;
    protected $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => UserRoleConst::CUSTOMER,
        ]);

        $this->service = ServiceCategory::factory()->create();
        $this->area = Area::factory()->create();
    }

    public function test_command_cancels_expired_jobs_and_sends_notification()
    {
        $threshold = now()->subMinutes(40); // 40 minutes ago

        // 1. Create a job that is expired (scheduled in the past, e.g. scheduled today, 40 mins ago)
        $expiredJob = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'area_id' => $this->area->id,
            'scheduled_date' => $threshold->toDateString(),
            'work_start_time' => $threshold->toTimeString(),
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        // 2. Create a job that is NOT expired (scheduled in the future, e.g. tomorrow)
        $futureJob = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'area_id' => $this->area->id,
            'scheduled_date' => now()->addDays(1)->toDateString(),
            'work_start_time' => '10:00:00',
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        // 3. Create a job that is NOT expired because it is only 10 minutes past schedule
        $recentJob = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'area_id' => $this->area->id,
            'scheduled_date' => now()->toDateString(),
            'work_start_time' => now()->subMinutes(10)->toTimeString(),
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        // Run the command
        $this->artisan('job:cancel-expired')
            ->expectsOutput('Starting checking for expired jobs...')
            ->expectsOutput('Successfully cancelled 1 expired jobs.')
            ->assertExitCode(0);

        // Verify expired job is changed to EXPIRED
        $this->assertDatabaseHas('t_jobs', [
            'id' => $expiredJob->id,
            'status' => JobStatusConst::EXPIRED,
        ]);

        // Verify other jobs are still WAITING_FOR_QUOTATION
        $this->assertDatabaseHas('t_jobs', [
            'id' => $futureJob->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $recentJob->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
        ]);

        // Verify push notification is created in DB
        $this->assertDatabaseHas('t_notifications', [
            'user_id' => $this->customer->id,
            'title' => 'Yêu cầu công việc hết hạn',
            'body' => 'Rất tiếc, hiện tại không có thợ nào ở gần nhận yêu cầu của bạn. Vui lòng đặt lại lịch mới nhé!',
        ]);
    }
}
