<?php

namespace Database\Seeders;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Role\RoleColumn;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Role::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $roles = CommonRolesConst::ROLES;

        foreach ($roles as $id => $name) {
            Role::create([
                RoleColumn::ID => $id,
                RoleColumn::CODE => 'R'.str_pad($id, 3, '0', STR_PAD_LEFT),
                RoleColumn::NAME => ucfirst($name),
                RoleColumn::STATUS => 'active',
                RoleColumn::NOTE => 'System Role '.ucfirst($name),
            ]);
        }
    }
}
