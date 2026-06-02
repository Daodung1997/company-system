<?php

namespace Database\Seeders;

use App\Constants\Commons\CommonStatusConst;
use App\Constants\Master\Models\Customer\CustomerColumn;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Customer::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $customerList = [
            [
                CustomerColumn::EMAIL => 'testCustomer@gmail.com',
                CustomerColumn::PASSWORD => 'customer@123',
            ],
            [
                CustomerColumn::EMAIL => 'thang.dk.scsoft@gmail.com',
                CustomerColumn::PASSWORD => 'customer@123',
            ],
        ];

        foreach ($customerList as $key => $customer) {
            Customer::create([
                CustomerColumn::FIRST_NAME => 'Test',
                CustomerColumn::LAST_NAME => 'Customer',
                CustomerColumn::EMAIL => $customer[CustomerColumn::EMAIL],
                CustomerColumn::PASSWORD => bcrypt($customer[CustomerColumn::PASSWORD]),
                CustomerColumn::PHONE => '0123456789',
                CustomerColumn::NATION => 'Test NATION',
                CustomerColumn::CITY => 'Test CITY',
                CustomerColumn::WARD => 'Test WARD',
                CustomerColumn::STREET => 'Test STREET',
                CustomerColumn::ADDRESS => 'Test Address',
                CustomerColumn::LOCALE => 'vi',
                CustomerColumn::STATUS => CommonStatusConst::ACTIVE,
            ]);
        }
    }
}
