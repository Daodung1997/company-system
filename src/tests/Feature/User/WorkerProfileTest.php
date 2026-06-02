<?php

namespace Tests\Feature\User;

use App\Models\Area;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $workerProfile;

    protected function setUp(): void
    {
        parent::setUp();
        // Create user and profile
        $this->user = User::factory()->create(['role' => 'worker']);
        $this->workerProfile = WorkerProfile::create([
            'user_id' => $this->user->id,
            'phone' => '0123456789',
            'gender' => 'male',
            'dob' => '1990-01-01',
            'profile_status' => 'incomplete',
        ]);
    }

    public function test_get_profile_success()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/worker/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->workerProfile->id)
            ->assertJsonPath('data.user.id', $this->user->id)
            ->assertJsonPath('data.gender', 'male')
            ->assertJsonPath('data.date_of_birth', '1990-01-01');
    }

    public function test_update_profile_success()
    {
        $data = [
            'phone' => '0987654321',
            'address' => 'Updated Address',
            'experience_years' => 5,
            'certificates' => ['Certificate A', 'Certificate B'],
        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/worker/profile', $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.phone', '0987654321')
            ->assertJsonPath('data.address', 'Updated Address')
            ->assertJsonPath('data.certificates.0', 'Certificate A')
            ->assertJsonPath('data.certificates.1', 'Certificate B');

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->user->id,
            'phone' => '0987654321',
        ]);

        $this->assertEquals(
            ['Certificate A', 'Certificate B'],
            $this->user->workerProfile->fresh()->certificates
        );
    }

    public function test_toggle_availability_success()
    {
        // Initial false
        $this->assertFalse((bool) $this->workerProfile->availability);

        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/worker/profile/availability');

        $response->assertStatus(200)
            ->assertJsonPath('data.availability', true);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->user->id,
            'availability' => 1,
        ]);

        // Toggle back
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/worker/profile/availability');

        $response->assertStatus(200)
            ->assertJsonPath('data.availability', false);
    }

    public function test_update_areas_success()
    {
        $area = Area::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/worker/profile/areas', [
                'area_ids' => [$area->id],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('m_worker_areas', [
            'worker_profile_id' => $this->workerProfile->id,
            'area_id' => $area->id,
        ]);
    }

    public function test_update_services_success()
    {
        $service = ServiceCategory::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/worker/profile/services', [
                'service_category_ids' => [$service->id],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('m_worker_services', [
            'worker_profile_id' => $this->workerProfile->id,
            'service_category_id' => $service->id,
        ]);
    }

    public function test_validation_errors()
    {
        // Test update areas with invalid id
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/worker/profile/areas', [
                'area_ids' => [99999],
            ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('area_ids.0', $response->json('messages'));

        // Test update services with invalid id
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/worker/profile/services', [
                'service_category_ids' => [99999],
            ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('service_category_ids.0', $response->json('messages'));
    }

    public function test_unauthorized_access()
    {
        $response = $this->getJson('/api/worker/profile');
        $response->assertStatus(401);
    }
}
