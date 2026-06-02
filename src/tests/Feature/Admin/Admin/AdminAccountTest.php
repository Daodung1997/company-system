<?php

namespace Tests\Feature\Admin\Admin;

use App\Constants\Master\Models\Admin\AdminStatusConst;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class AdminAccountTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase, WithFaker;

    protected $superAdmin;

    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminWithAllPermissions();
        $this->superAdmin = $this->admin;
        $this->role = $this->superAdminRole;
    }

    public function test_admin_can_list_admins()
    {
        Admin::factory()->count(3)->create();

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/admins');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'code', 'name', 'email', 'status', 'roles'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_create_admin()
    {
        $payload = [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'Password123!',
            'status' => AdminStatusConst::ACTIVE,
            'role_ids' => [$this->role->id],
        ];

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson('/api/admin/admins', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.admin.email', 'newadmin@example.com');

        $this->assertDatabaseHas('m_admins', ['email' => 'newadmin@example.com']);
    }

    public function test_admin_cannot_deactivate_self()
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson("/api/admin/admins/{$this->superAdmin->id}/toggle-status", [
                'status' => AdminStatusConst::INACTIVE,
            ]);

        $response->assertStatus(400); // CANNOT_DEACTIVATE_SELF
    }

    public function test_admin_cannot_delete_self()
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->deleteJson("/api/admin/admins/{$this->superAdmin->id}");

        $response->assertStatus(400); // CANNOT_DELETE_SELF
    }

    public function test_admin_can_update_other_admin_status()
    {
        $otherAdmin = Admin::factory()->create(['status' => AdminStatusConst::ACTIVE]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson("/api/admin/admins/{$otherAdmin->id}/toggle-status", [
                'status' => AdminStatusConst::INACTIVE,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.admin.status', (string) AdminStatusConst::INACTIVE);
    }

    // ══════════════════════════════════════════════════
    // VALIDATION CASES (422) - /api-test standard
    // ══════════════════════════════════════════════════

    public function test_cannot_create_admin_without_name()
    {
        $payload = [
            'email' => 'newadmin@example.com',
            'password' => 'Password123!',
            'status' => AdminStatusConst::ACTIVE,
            'role_ids' => [$this->role->id],
        ];

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson('/api/admin/admins', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 422,
                'messages' => ['name.required'],
            ]);
    }

    public function test_cannot_create_duplicate_admin_email()
    {
        Admin::factory()->create(['email' => 'duplicate@example.com']);

        $payload = [
            'name' => 'Duplicate Admin',
            'email' => 'duplicate@example.com',
            'password' => 'Password123!',
            'status' => AdminStatusConst::ACTIVE,
            'role_ids' => [$this->role->id],
        ];

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson('/api/admin/admins', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 422,
                'messages' => ['email.unique'],
            ]);
    }
}
