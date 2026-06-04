<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanySetting::updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Công ty Cổ phần Giải pháp Công nghệ Việt Nam',
                'company_name_kana' => 'VIETNAM TECH SOLUTIONS',
                'tax_code' => '0109283746',
                'corporate_number' => 'GPKD: 1234567890123',
                'address_registered' => 'Tầng 12, Tòa nhà Keangnam Landmark 72, Đường Phạm Hùng, Phường Mễ Trì, Quận Nam Từ Liêm, Hà Nội',
                'legal_representative' => 'Nguyễn Văn A',
                'representative_title' => 'Tổng Giám đốc',
                'representative_id_number' => '001092837465',
                'representative_id_date' => '2020-05-15',
                'representative_id_place' => 'Cục Cảnh sát Quản lý hành chính về trật tự xã hội',
                'charter_capital' => '10.000.000.000 VNĐ',
                'phone_number' => '02439876543',
                'email' => 'contact@techsolutions.com.vn',
                'fax' => '02439876544',
                'postcode' => '100000',
                'address' => 'Tòa nhà Keangnam Landmark 72, Đường Phạm Hùng, Phường Mễ Trì, Quận Nam Từ Liêm, Hà Nội',
                'website' => 'https://techsolutions.com.vn',
                'hanko_seal_path' => '/seals/default_seal.png',
            ]
        );
    }
}
