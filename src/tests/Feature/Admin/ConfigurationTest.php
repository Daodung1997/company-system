<?php

namespace Tests\Feature\Admin;

use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class ConfigurationTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase;

    protected $admin;

    protected $endpoint = '/api/admin/config/job-assignment';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminWithAllPermissions();
    }

    public function test_admin_can_get_job_assignment_config()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'scan_radius',
                    'max_workers_per_job',
                    'rating_weight',
                ],
            ]);
    }

    public function test_admin_can_update_job_assignment_config()
    {
        $payload = [
            'scan_radius' => 15,
            'max_workers_per_job' => 10,
            'rating_weight' => 0.6,
            'distance_weight' => 0.2, // Sum 1.0 logic not strictly enforced yet
            'response_rate_weight' => 0.2,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson($this->endpoint, $payload);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'scan_radius' => 15,
                    'max_workers_per_job' => 10,
                ],
            ]);

        // Verify DB
        $config = Configuration::where('key', 'job_assignment_config')->first();
        $this->assertNotNull($config);
        $value = json_decode($config->value, true);
        $this->assertEquals(15, $value['scan_radius']);
        $this->assertEquals(10, $value['max_workers_per_job']);
    }

    public function test_update_config_validation()
    {
        $payload = [
            'scan_radius' => 'invalid',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson($this->endpoint, $payload);

        $response->assertStatus(422);
    }
}
