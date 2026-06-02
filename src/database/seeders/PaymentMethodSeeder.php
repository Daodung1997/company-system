<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'code' => 'PM_CASH',
                'name' => 'Tiền mặt',
                'type' => \App\Constants\Master\Models\Payment\PaymentMethodTypeConst::CASH,
                'status' => \App\Constants\Commons\CommonStatusConst::ACTIVE,
                'sort_order' => 1,
            ],
            [
                'code' => 'PM_VNPAY',
                'name' => 'VNPAY',
                'type' => \App\Constants\Master\Models\Payment\PaymentMethodTypeConst::VNPAY,
                'status' => \App\Constants\Commons\CommonStatusConst::ACTIVE,
                'sort_order' => 3,
            ],
        ];

        foreach ($methods as $method) {
            \App\Models\PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
