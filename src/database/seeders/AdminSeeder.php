<?php

namespace Database\Seeders;

use App\Constants\Commons\CommonPermissionConst;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions for admin guard
        $allPermissions = CommonPermissionConst::getValues();
        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'admin',
            ]);
        }

        // Create super_admin role and assign all permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'admin']);
        $superAdminRole->syncPermissions($allPermissions);

        // Create or find super admin
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@viecvat.com'],
            [
                'code' => 'A'.str_pad(1, 5, '0', STR_PAD_LEFT),
                'name' => 'Super Admin',
                'password' => Hash::make('Password123!'),
                'status' => \App\Constants\Master\Models\Admin\AdminStatusConst::ACTIVE,
            ]
        );
        $admin->assignRole($superAdminRole);

        // Create 10 random admins only if none exist besides super admin
        if (Admin::count() <= 1) {
            $otherAdmins = Admin::factory(10)->create();
            foreach ($otherAdmins as $otherAdmin) {
                $otherAdmin->assignRole($superAdminRole);
            }
        }
    }
}
