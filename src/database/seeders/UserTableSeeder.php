<?php

namespace Database\Seeders;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Commons\CommonStatusConst;
use App\Constants\Master\Models\User\UserColumn;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $userList = [
            [
                UserColumn::EMAIL => 'testEmployee@gmail.com',
                UserColumn::PASSWORD => 'employee@123',
                UserColumn::ROLE_ID => CommonRolesConst::EMPLOYEE,
            ],
            [
                UserColumn::EMAIL => 'testAdmin@gmail.com',
                UserColumn::PASSWORD => 'admin@123',
                UserColumn::ROLE_ID => CommonRolesConst::ADMIN,
            ],
        ];
        //        $department = Department::all();
        //        $position = Position::all();

        foreach ($userList as $key => $user) {
            User::create([
                UserColumn::FIRST_NAME => 'Test'.($key + 1),
                UserColumn::LAST_NAME => 'User',
                UserColumn::EMAIL => $user[UserColumn::EMAIL],
                UserColumn::PASSWORD => bcrypt($user[UserColumn::PASSWORD]),
                UserColumn::PHONE => '0123456789',
                UserColumn::POSITION_NAME => 'Position Name',
                UserColumn::STATUS => CommonStatusConst::ACTIVE,
                UserColumn::ROLE_ID => $user[UserColumn::ROLE_ID],
                UserColumn::LOCALE => 'vi',
            ]);
        }
    }
}
