<?php

namespace Database\Seeders;

use App\Constants\Commons\CommonPermissionConst;
use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\User\UserColumn;
use App\Constants\Master\Models\User\UserPermissionColumn;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        UserPermission::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $userList = User::where(UserColumn::ROLE_ID, CommonRolesConst::ADMIN)->get();
        $allPermission = CommonPermissionConst::getValues();
        foreach ($userList as $user) {
            foreach ($allPermission as $permission) {
                UserPermission::create([
                    UserPermissionColumn::USER_CODE => $user->{UserColumn::CODE},
                    UserPermissionColumn::PERMISSION => $permission,
                ]);
            }
        }
    }
}
