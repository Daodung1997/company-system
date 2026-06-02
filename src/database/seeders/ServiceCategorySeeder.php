<?php

namespace Database\Seeders;

use App\Constants\Master\Models\ServiceCategory\ServiceCategoryLevelConst;
use App\Constants\Master\Models\ServiceCategory\ServiceCategoryStatusConst;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Seed 2-level service categories: 12 main + 54 sub.
     */
    public function run(): void
    {
        $categories = [
            'Vệ sinh nhà cửa' => [
                'description' => 'Dịch vụ vệ sinh tổng thể',
                'children' => [
                    'Dịch vụ giúp việc nhà',
                    'Vệ sinh máy lạnh',
                    'Vệ sinh máy hút mùi',
                    'Vệ sinh máy giặt',
                    'Vệ sinh tủ lạnh',
                    'Vệ sinh khu vực nước (bếp + toilet)',
                    'Vệ sinh phòng tắm',
                    'Làm sạch hệ thống bồn tắm',
                    'Vệ sinh lavabo',
                    'Vệ sinh toilet',
                    'Vệ sinh bếp',
                    'Vệ sinh tổng thể nhà',
                    'Vệ sinh ban công',
                    'Vệ sinh cửa sổ',
                    'Vệ sinh giấy dán tường',
                    'Vệ sinh sofa',
                    'Vệ sinh nệm',
                ],
            ],
            'Thu gom đồ không dùng' => [
                'description' => null,
                'children' => [
                    'Thu gom đồ bỏ',
                    'Dọn đồ sau khi có người mất',
                    'Dọn nhà tích trữ rác',
                ],
            ],
            'Chuyển nhà' => [
                'description' => null,
                'children' => [
                    'Chuyển nhà giá rẻ',
                    'Vận chuyển xe máy / xe đạp',
                    'Vận chuyển đồ nội thất',
                    'Vận chuyển piano',
                    'Dịch vụ đóng / mở đồ chuyển nhà',
                ],
            ],
            'Làm vườn / cảnh quan' => [
                'description' => null,
                'children' => [
                    'Cắt tỉa cây',
                    'Chặt cây / nhổ gốc',
                    'Nhổ cỏ thủ công',
                    'Cắt cỏ bằng máy',
                    'Lắp cỏ nhân tạo',
                ],
            ],
            'Diệt côn trùng / động vật gây hại' => [
                'description' => null,
                'children' => [
                    'Diệt côn trùng',
                    'Diệt động vật hoang dã',
                ],
            ],
            'Khóa & an ninh' => [
                'description' => null,
                'children' => [
                    'Thay / lắp khóa',
                    'Lắp camera an ninh',
                    'Mở khóa cửa chính',
                ],
            ],
            'Cải tạo nhà' => [
                'description' => null,
                'children' => [
                    'Cải tạo giấy dán tường',
                    'Thay lưới cửa',
                    'Thay tatami',
                    'Thay sàn gỗ',
                    'Sơn tường ngoài',
                ],
            ],
            'Lắp đặt thiết bị điện' => [
                'description' => null,
                'children' => [
                    'Lắp máy lạnh',
                    'Tháo máy lạnh',
                    'Sửa máy lạnh',
                    'Lắp máy giặt',
                    'Cài đặt máy tính',
                ],
            ],
            'Sửa chữa / lắp ráp nội thất' => [
                'description' => null,
                'children' => [
                    'Lắp ráp nội thất',
                    'Sửa đồ nội thất',
                ],
            ],
            'Dịch vụ cho văn phòng / cửa hàng' => [
                'description' => null,
                'children' => [
                    'Vệ sinh sàn',
                    'Vệ sinh ống gió',
                ],
            ],
            'Sửa chữa hệ thống nước' => [
                'description' => null,
                'children' => [
                    'Thay vòi nước',
                    'Sửa tắc ống nước',
                ],
            ],
            'Dịch vụ xe hơi' => [
                'description' => null,
                'children' => [
                    'Vệ sinh nội thất xe',
                    'Lắp GPS xe',
                    'Lắp camera hành trình',
                ],
            ],
        ];

        // Truncate and re-seed
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ServiceCategory::query()->forceDelete();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $mainOrder = 1;
        foreach ($categories as $mainName => $mainData) {
            $main = ServiceCategory::create([
                'name' => $mainName,
                'description' => $mainData['description'],
                'status' => ServiceCategoryStatusConst::ACTIVE,
                'sort_order' => $mainOrder,
                'level' => ServiceCategoryLevelConst::MAIN,
                'parent_id' => null,
            ]);

            $subOrder = 1;
            foreach ($mainData['children'] as $childName) {
                ServiceCategory::create([
                    'name' => $childName,
                    'status' => ServiceCategoryStatusConst::ACTIVE,
                    'sort_order' => $subOrder,
                    'level' => ServiceCategoryLevelConst::SUB,
                    'parent_id' => $main->id,
                ]);
                $subOrder++;
            }

            $mainOrder++;
        }
    }
}
