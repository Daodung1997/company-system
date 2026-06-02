<?php

namespace Tests\Feature\Admin\Worker;

use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\WorkerProfile\WorkerActivityStatus;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class WorkerManagementTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase;

    protected $admin;

    protected $worker;

    protected $workerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminWithAllPermissions();

        // Create Worker User
        $this->worker = User::factory()->create([
            'role' => UserRoleConst::WORKER,
            'name' => 'Test Worker',
            'email' => 'worker@example.com',
        ]);

        // Create Worker Profile
        $this->workerProfile = WorkerProfile::create([
            'user_id' => $this->worker->id,
            'phone' => '0987654321',
            'profile_status' => WorkerProfileStatus::PENDING,
            'activity_status' => WorkerActivityStatus::ACTIVE,
        ]);
    }

    public function test_admin_can_list_workers()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/workers');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['data' => [['id', 'role', 'profile_status', 'activity_status']]]])
            ->assertJsonPath('data.data.0.id', $this->worker->id);
    }

    public function test_admin_can_list_workers_with_filters()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/workers?filters[profile_status]='.WorkerProfileStatus::PENDING.'&filters[activity_status]='.WorkerActivityStatus::ACTIVE);

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.id', $this->worker->id);
    }

    public function test_admin_can_search_workers_by_name()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/workers?search[name]=Test Worker');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.id', $this->worker->id);
    }

    public function test_admin_can_search_workers_by_email()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/workers?search[email]=worker@example.com');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.id', $this->worker->id);
    }

    public function test_admin_can_search_workers_by_phone()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/workers?search[phone]=0987654321');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.id', $this->worker->id);
    }

    public function test_admin_can_search_workers_with_multiple_fields()
    {
        // Should match because it's an OR condition in our query builder
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/workers?search[name]=NonExistent&search[phone]=0987654321');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.id', $this->worker->id);
    }

    public function test_list_workers_empty_on_non_matching_filter()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/workers?filters[profile_status]='.WorkerProfileStatus::REJECTED);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.data');
    }

    public function test_admin_can_view_worker_detail()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/workers/{$this->worker->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->worker->id)
            ->assertJsonPath('data.profile_status', WorkerProfileStatus::PENDING);
    }

    public function test_admin_can_approve_worker()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/workers/{$this->worker->id}/approve");

        $response->assertStatus(200);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->worker->id,
            'profile_status' => WorkerProfileStatus::APPROVED,
            'activity_status' => WorkerActivityStatus::ACTIVE,
        ]);
    }

    public function test_admin_can_reject_worker_with_reason()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/workers/{$this->worker->id}/reject", [
                'reason' => 'Invalid documents',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->worker->id,
            'profile_status' => WorkerProfileStatus::REJECTED,
            'rejection_reason' => 'Invalid documents',
        ]);
    }

    public function test_admin_cannot_reject_worker_without_reason()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/workers/{$this->worker->id}/reject", []);

        $response->assertStatus(422)
            ->assertJson([
                'messages' => ['The reason field is required.'],
            ]);
    }

    public function test_admin_can_suspend_and_activate_worker()
    {
        // Suspend
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/workers/{$this->worker->id}/suspend", [
                'reason' => 'Test suspend reason',
            ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->worker->id,
            'activity_status' => WorkerActivityStatus::SUSPENDED,
            'suspend_reason' => 'Test suspend reason',
        ]);

        // Activate
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/workers/{$this->worker->id}/activate");
        $response->assertStatus(200);
        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->worker->id,
            'activity_status' => WorkerActivityStatus::ACTIVE,
        ]);
    }

    public function test_admin_cannot_suspend_worker_without_reason()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/workers/{$this->worker->id}/suspend", []);

        $response->assertStatus(422)
            ->assertJson([
                'messages' => ['The reason field is required.'],
            ]);
    }

    public function test_admin_can_update_worker_profile()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/workers/{$this->worker->id}", [
                'name' => 'Updated Worker Name',
                'phone' => '0909123456',
                'address' => '123 New Address',
                'experience_years' => 5,
                'skill_description' => 'Expert in plumbing',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Worker Name')
            ->assertJsonPath('data.phone', '0909123456');

        $this->assertDatabaseHas('m_users', [
            'id' => $this->worker->id,
            'name' => 'Updated Worker Name',
        ]);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->worker->id,
            'phone' => '0909123456',
            'address' => '123 New Address',
            'experience_years' => 5,
            'skill_description' => 'Expert in plumbing',
        ]);
    }

    public function test_admin_can_update_worker_partial_fields()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/workers/{$this->worker->id}", [
                'phone' => '0912345678',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->worker->id,
            'phone' => '0912345678',
        ]);

        // Name should remain unchanged
        $this->assertDatabaseHas('m_users', [
            'id' => $this->worker->id,
            'name' => 'Test Worker',
        ]);
    }

    public function test_update_worker_returns_404_for_non_existent_worker()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson('/api/admin/workers/99999', [
                'name' => 'Some Name',
            ]);

        $response->assertStatus(404); // Expect 404 Not Found
    }

    public function test_guest_cannot_update_worker()
    {
        $response = $this->putJson("/api/admin/workers/{$this->worker->id}", [
            'name' => 'Hacker Name',
        ]);

        $response->assertStatus(401);
    }
}
