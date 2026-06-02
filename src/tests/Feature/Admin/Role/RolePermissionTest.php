<?php

namespace Tests\Feature\Admin\Role;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class RolePermissionTest extends TestCase
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

    public function test_admin_can_list_roles()
    {
        Role::create(['name' => 'manager', 'guard_name' => 'admin']);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'guard_name', 'permissions', 'users_count'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_create_role()
    {
        $permission = Permission::firstOrCreate(['name' => 'manage_users', 'guard_name' => 'admin']);

        $payload = [
            'name' => 'editor',
            'permission_ids' => [$permission->id],
        ];

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson('/api/admin/roles', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.role.name', 'editor');

        $this->assertDatabaseHas('m_roles', ['name' => 'editor']);
    }

    public function test_cannot_delete_super_admin_role()
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->deleteJson("/api/admin/roles/{$this->role->id}");

        $response->assertStatus(400); // Bad Request per ExceptionCode::ROLE_SUPER_ADMIN_CANNOT_BE_DELETED
    }

    public function test_cannot_update_super_admin_role()
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->putJson("/api/admin/roles/{$this->role->id}", [
                'name' => 'super_admin_modified',
            ]);

        $response->assertStatus(400);
    }

    public function test_admin_can_delete_role()
    {
        $roleToDelete = Role::create(['name' => 'temp_role', 'guard_name' => 'admin']);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->deleteJson("/api/admin/roles/{$roleToDelete->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('m_roles', ['id' => $roleToDelete->id]);
    }

    // ══════════════════════════════════════════════════
    // VALIDATION CASES (422) - /api-test standard
    // ══════════════════════════════════════════════════

    public function test_cannot_create_role_without_name()
    {
        $payload = [
            'permission_ids' => [],
        ];

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson('/api/admin/roles', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 422,
                'messages' => ['name.required'],
            ]);
    }

    public function test_cannot_create_duplicate_role_name()
    {
        Role::create(['name' => 'duplicate_role', 'guard_name' => 'admin']);

        $payload = [
            'name' => 'duplicate_role',
            'permission_ids' => [],
        ];

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson('/api/admin/roles', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 422,
                'messages' => ['name.unique'],
            ]);
    }
}
