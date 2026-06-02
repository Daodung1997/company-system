<?php

namespace Tests\Traits;

use App\Constants\Commons\CommonPermissionConst;
use App\Models\Admin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait CreatesAdminUser
{
    /**
     * Create an admin user with super_admin role and all permissions.
     */
    protected function createAdminWithAllPermissions(array $attributes = []): Admin
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed all permissions
        foreach (CommonPermissionConst::getValues() as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'admin',
            ]);
        }

        // Create super_admin role with all permissions
        $this->superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'admin']);
        $this->superAdminRole->syncPermissions(CommonPermissionConst::getValues());

        // Create admin and assign role
        $this->admin = Admin::factory()->create($attributes);
        $this->admin->assignRole($this->superAdminRole);

        return $this->admin;
    }

    /**
     * Create an admin user with specific permissions only.
     */
    protected function createAdminWithPermissions(array $permissions, array $attributes = []): Admin
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed specified permissions
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'admin',
            ]);
        }

        $role = Role::firstOrCreate(['name' => 'test_role', 'guard_name' => 'admin']);
        $role->syncPermissions($permissions);

        $admin = Admin::factory()->create($attributes);
        $admin->assignRole($role);

        return $admin;
    }
}
