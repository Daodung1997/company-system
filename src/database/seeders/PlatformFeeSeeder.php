<?php

namespace Database\Seeders;

use App\Constants\Master\Models\PlatformFee\PlatformFeeCodeConst;
use App\Constants\Master\Models\PlatformFee\PlatformFeeStatusConst;
use App\Constants\Master\Models\PlatformFee\PlatformFeeTypeConst;
use App\Models\PlatformFee;
use Illuminate\Database\Seeder;

class PlatformFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fees = [
            [
                'code' => PlatformFeeCodeConst::PLATFORM_FEE,
                'name' => 'Phí nền tảng',
                'description' => 'Phí nền tảng thu từ mỗi giao dịch hoàn thành.',
                'fee_type' => PlatformFeeTypeConst::PERCENTAGE,
                'amount' => 10, // 10%
                'start_date' => now(),
                'status' => PlatformFeeStatusConst::ACTIVE,
            ],

            [
                'code' => PlatformFeeCodeConst::WORKER_REG_FEE,
                'name' => 'Phí đăng ký thợ',
                'description' => 'Phí đăng ký tài khoản thợ trên nền tảng.',
                'fee_type' => PlatformFeeTypeConst::FIXED,
                'amount' => 0,
                'start_date' => now(),
                'status' => PlatformFeeStatusConst::INACTIVE,
            ],
            [
                'code' => PlatformFeeCodeConst::WITHDRAWAL_FEE,
                'name' => 'Phí rút tiền',
                'description' => 'Phí rút tiền từ ví thợ về tài khoản ngân hàng.',
                'fee_type' => PlatformFeeTypeConst::FIXED,
                'amount' => 5000,
                'start_date' => now(),
                'status' => PlatformFeeStatusConst::ACTIVE,
            ],
        ];

        foreach ($fees as $fee) {
            PlatformFee::firstOrCreate(
                ['code' => $fee['code']],
                $fee
            );
        }
    }
}
