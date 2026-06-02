<?php

namespace Database\Seeders;

use App\Constants\Commons\CommonStatusConst;
use App\Constants\Master\Models\Discount\DiscountTypeConst;
use App\Models\Discount;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Specific realistic Vietnamese promotions
        $promotions = [
            [
                'code' => 'VIECVAT10',
                'title' => 'Giảm giá 10% cho khách hàng mới',
                'discount_type' => DiscountTypeConst::PERCENTAGE,
                'discount_value' => 10.00,
                'max_discount_amount' => 50000.00,
                'min_order_amount' => 100000.00,
                'total_quantity' => 500,
                'used_quantity' => 0,
                'max_uses_per_user' => 1,
                'start_date' => Carbon::now()->startOfDay(),
                'end_date' => Carbon::now()->addMonths(6)->endOfDay(),
                'status' => CommonStatusConst::ACTIVE,
                'note' => 'Chương trình ưu đãi giảm 10% tối đa 50k dành riêng cho khách hàng đăng ký tài khoản mới và đặt dịch vụ lần đầu tiên.',
            ],
            [
                'code' => 'CHAOBAN50',
                'title' => 'Chào bạn mới - Nhận ngay 50k',
                'discount_type' => DiscountTypeConst::FIXED_AMOUNT,
                'discount_value' => 50000.00,
                'max_discount_amount' => null,
                'min_order_amount' => 150000.00,
                'total_quantity' => 200,
                'used_quantity' => 0,
                'max_uses_per_user' => 1,
                'start_date' => Carbon::now()->startOfDay(),
                'end_date' => Carbon::now()->addMonths(3)->endOfDay(),
                'status' => CommonStatusConst::ACTIVE,
                'note' => 'Giảm ngay 50,000 VND cho đơn hàng từ 150,000 VND trở lên áp dụng cho tất cả dịch vụ dọn dẹp vệ sinh và sửa chữa thiết bị điện.',
            ],
            [
                'code' => 'HE2026',
                'title' => 'Chào hè rực rỡ - Giảm sâu 20%',
                'discount_type' => DiscountTypeConst::PERCENTAGE,
                'discount_value' => 20.00,
                'max_discount_amount' => 100000.00,
                'min_order_amount' => 200000.00,
                'total_quantity' => 100,
                'used_quantity' => 0,
                'max_uses_per_user' => 2,
                'start_date' => Carbon::now()->startOfDay(),
                'end_date' => Carbon::now()->addMonths(2)->endOfDay(),
                'status' => CommonStatusConst::ACTIVE,
                'note' => 'Khuyến mãi hè sôi động giảm giá 20% cho mỗi dịch vụ đặt tối thiểu 200k. Mỗi khách hàng được dùng tối đa 2 lần trong suốt thời gian diễn ra chiến dịch.',
            ],
            [
                'code' => 'CUOITUAN30',
                'title' => 'Cuối tuần thảnh thơi - Giảm 30k',
                'discount_type' => DiscountTypeConst::FIXED_AMOUNT,
                'discount_value' => 30000.00,
                'max_discount_amount' => null,
                'min_order_amount' => 120000.00,
                'total_quantity' => 150,
                'used_quantity' => 0,
                'max_uses_per_user' => 1,
                'start_date' => Carbon::now()->startOfDay(),
                'end_date' => Carbon::now()->addMonths(4)->endOfDay(),
                'status' => CommonStatusConst::ACTIVE,
                'note' => 'Mã giảm giá cuối tuần thảnh thơi dọn dẹp nhà cửa. Giảm ngay 30,000 VND cho các đơn đặt lịch thực hiện vào thứ Bảy và Chủ Nhật.',
            ],
            [
                'code' => 'EXPIRED20',
                'title' => 'Ưu đãi tri ân - Đã hết hạn',
                'discount_type' => DiscountTypeConst::PERCENTAGE,
                'discount_value' => 15.00,
                'max_discount_amount' => 40000.00,
                'min_order_amount' => 100000.00,
                'total_quantity' => 300,
                'used_quantity' => 280,
                'max_uses_per_user' => 1,
                'start_date' => Carbon::now()->subMonths(3)->startOfDay(),
                'end_date' => Carbon::now()->subDay()->endOfDay(),
                'status' => CommonStatusConst::ACTIVE,
                'note' => 'Mã giảm giá tri ân khách hàng thân thiết. Voucher này đã quá hạn sử dụng.',
            ],
            [
                'code' => 'INACTIVE15',
                'title' => 'Khuyến mãi đặc biệt - Tạm ngưng',
                'discount_type' => DiscountTypeConst::PERCENTAGE,
                'discount_value' => 15.00,
                'max_discount_amount' => 60000.00,
                'min_order_amount' => 150000.00,
                'total_quantity' => 500,
                'used_quantity' => 45,
                'max_uses_per_user' => 1,
                'start_date' => Carbon::now()->startOfDay(),
                'end_date' => Carbon::now()->addMonths(6)->endOfDay(),
                'status' => CommonStatusConst::INACTIVE,
                'note' => 'Voucher giảm giá đặc biệt tạm thời bị khóa/ngưng hoạt động từ phía quản trị viên để điều chỉnh ngân sách.',
            ],
            [
                'code' => 'SOLDOUT50',
                'title' => 'Tri ân khách hàng - Đã hết lượt dùng',
                'discount_type' => DiscountTypeConst::FIXED_AMOUNT,
                'discount_value' => 50000.00,
                'max_discount_amount' => null,
                'min_order_amount' => 150000.00,
                'total_quantity' => 50,
                'used_quantity' => 50,
                'max_uses_per_user' => 1,
                'start_date' => Carbon::now()->subMonths(1)->startOfDay(),
                'end_date' => Carbon::now()->addMonths(1)->endOfDay(),
                'status' => CommonStatusConst::ACTIVE,
                'note' => 'Mã ưu đãi tri ân số lượng giới hạn cực kỳ hấp dẫn. Mã này hiện đã được sử dụng hết số lượng phát hành tối đa (50/50).',
            ],
        ];

        foreach ($promotions as $promo) {
            Discount::updateOrCreate(
                ['code' => $promo['code']],
                $promo
            );
        }

        // 2. Generate 15 additional random discount codes via factory to demonstrate pagination
        $currentCount = Discount::count();
        if ($currentCount < 20) {
            Discount::factory()->count(20 - $currentCount)->create();
        }
    }
}
