<?php

namespace Tests\Feature\Worker;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_worker_home_success()
    {
        $worker = \App\Models\User::factory()->create([
            'role' => 'worker',
            'status' => 1,
        ]);
        \App\Models\WorkerProfile::factory()->create([
            'user_id' => $worker->id,
            'availability' => true,
        ]);

        $response = $this->actingAs($worker, 'api')->getJson('/api/worker/home');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'worker' => ['name', 'avatar', 'is_online'],
                    'summary' => ['active_jobs', 'pending_quotes', 'in_progress', 'completed'],
                    'suggested_jobs',
                    'my_jobs',
                ],
            ]);
    }

    public function test_get_worker_home_excludes_expired_jobs()
    {
        $worker = \App\Models\User::factory()->create([
            'role' => 'worker',
            'status' => 1,
        ]);
        \App\Models\WorkerProfile::factory()->create([
            'user_id' => $worker->id,
            'availability' => true,
        ]);

        // Create an expired job assigned to worker
        $expiredJob = \App\Models\Job::factory()->create([
            'worker_id' => $worker->id,
            'status' => \App\Constants\Master\Models\Job\JobStatusConst::EXPIRED,
        ]);

        // Create an in_progress job assigned to worker
        $activeJob = \App\Models\Job::factory()->create([
            'worker_id' => $worker->id,
            'status' => \App\Constants\Master\Models\Job\JobStatusConst::IN_PROGRESS,
        ]);

        $response = $this->actingAs($worker, 'api')->getJson('/api/worker/home');

        $response->assertStatus(200);
        $myJobs = $response->json('data.my_jobs');
        
        $myJobIds = collect($myJobs)->pluck('id')->toArray();
        
        // Assert that the active job is returned, but the expired job is NOT
        $this->assertContains($activeJob->id, $myJobIds);
        $this->assertNotContains($expiredJob->id, $myJobIds);
    }

    public function test_toggle_online_status_success()
    {
        $worker = \App\Models\User::factory()->create(['role' => 'worker']);
        \App\Models\WorkerProfile::factory()->create([
            'user_id' => $worker->id,
            'availability' => false,
        ]);

        $response = $this->actingAs($worker, 'api')->postJson('/api/worker/home/toggle-status', [
            'is_online' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $worker->id,
            'availability' => 1,
        ]);
    }

    public function test_toggle_online_status_validation_error()
    {
        $worker = \App\Models\User::factory()->create(['role' => 'worker']);

        $response = $this->actingAs($worker, 'api')->postJson('/api/worker/home/toggle-status', []);

        $response->assertStatus(422)
            ->assertJsonFragment(['messages' => ['is_online.required']]);
    }
}
