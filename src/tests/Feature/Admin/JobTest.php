<?php

namespace Tests\Feature\Admin;

use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class JobTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase, WithFaker;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminWithAllPermissions();
    }

    public function test_admin_can_list_jobs()
    {
        Job::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/jobs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => ['id', 'code', 'status', 'description'],
                ],
                'total',
                'current_page',
                'limit',
                'metadata',
            ],
        ]);
    }

    public function test_admin_can_view_job_detail()
    {
        $job = Job::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $job->id);
    }
}
