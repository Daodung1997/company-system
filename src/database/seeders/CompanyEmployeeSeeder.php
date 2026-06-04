<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Clean existing records to prevent unique constraints duplicate error
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('departments')->truncate();
        DB::table('job_titles')->truncate();
        DB::table('employees')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 1. Seed Departments
        $itDepId = DB::table('departments')->insertGetId([
            'code' => 'DEP00001',
            'name' => 'Phòng Công nghệ Thông tin (IT)',
            'description' => 'Chịu trách nhiệm phát triển phần mềm và an toàn thông tin.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $hrDepId = DB::table('departments')->insertGetId([
            'code' => 'DEP00002',
            'name' => 'Phòng Hành chính Nhân sự (HR)',
            'description' => 'Quản lý nhân lực, phúc lợi và tuân thủ nội bộ.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seed Job Titles (Chức vụ phòng ban)
        // IT department job titles
        $itManagerTitleId = DB::table('job_titles')->insertGetId([
            'code' => 'JOB00001',
            'department_id' => $itDepId,
            'name' => 'Trưởng phòng IT',
            'description' => 'Quản lý toàn bộ hạ tầng kỹ thuật và dự án phần mềm.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itSeniorTitleId = DB::table('job_titles')->insertGetId([
            'code' => 'JOB00002',
            'department_id' => $itDepId,
            'name' => 'Kỹ sư phần mềm Senior',
            'description' => 'Phát triển các hệ thống cốt lõi và hướng dẫn Junior.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // HR department job titles
        $hrManagerTitleId = DB::table('job_titles')->insertGetId([
            'code' => 'JOB00003',
            'department_id' => $hrDepId,
            'name' => 'Trưởng phòng HR',
            'description' => 'Quản trị nhân sự và hoạch định chiến lược tuyển dụng.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $hrStaffTitleId = DB::table('job_titles')->insertGetId([
            'code' => 'JOB00004',
            'department_id' => $hrDepId,
            'name' => 'Chuyên viên tuyển dụng',
            'description' => 'Tìm kiếm ứng viên và thực hiện các thủ tục tiếp nhận.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Seed Employee (Admin Account for login)
        DB::table('employees')->insert([
            'department_id' => $itDepId,
            'job_title_id' => $itManagerTitleId,
            'code' => 'EMP00001',
            'full_name' => 'Nguyễn Văn Quản Trị',
            'full_name_kana' => 'グエン・ヴァン・クアン・チ',
            'romaji_name' => 'Nguyen Van Quan Tri',
            'date_of_birth' => '1990-05-15',
            'gender' => 'MALE',
            'hometown' => 'Hà Nội, Việt Nam',
            'place_of_birth' => 'Bệnh viện Phụ sản Hà Nội',
            'nationality' => 'Việt Nam',
            'ethnicity' => 'Kinh',
            'religion' => 'Không',
            'email' => 'admin@compliance.vn',
            'phone' => '0987654321',
            'password' => Hash::make('P@ssw0rd123'),
            'identity_type' => 'CCCD',
            'identity_number' => '001095001234',
            'address_registered' => 'Số 12, ngõ 34, phố Lê Thanh Nghị, Bách Khoa, Hai Bà Trưng, Hà Nội',
            'address_current' => 'Số 56, đường Trần Đại Nghĩa, Đồng Tâm, Hai Bà Trưng, Hà Nội',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
            'join_date' => '2025-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Seed another Employee (Manager Account for login by phone)
        DB::table('employees')->insert([
            'department_id' => $hrDepId,
            'job_title_id' => $hrManagerTitleId,
            'code' => 'EMP00002',
            'full_name' => 'Trần Thị Nhân Sự',
            'full_name_kana' => 'チャン・ティ・ニャン・ス',
            'romaji_name' => 'Tran Thi Nhan Su',
            'date_of_birth' => '1993-10-20',
            'gender' => 'FEMALE',
            'hometown' => 'Nam Định, Việt Nam',
            'place_of_birth' => 'Bệnh viện Đa khoa Nam Định',
            'nationality' => 'Việt Nam',
            'ethnicity' => 'Kinh',
            'religion' => 'Phật giáo',
            'email' => 'manager@compliance.vn',
            'phone' => '0912345678',
            'password' => Hash::make('P@ssw0rd123'),
            'identity_type' => 'ZAIRYU_CARD',
            'identity_number' => 'AB1234567',
            'address_registered' => 'Xóm 3, xã Xuân Phương, huyện Xuân Trường, tỉnh Nam Định',
            'address_current' => 'Phòng 405, Chung cư Green Hills, Quận 7, TP. Hồ Chí Minh',
            'role' => 'MANAGER',
            'status' => 'ACTIVE',
            'join_date' => '2025-02-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed HR Account
        DB::table('employees')->insert([
            'department_id' => $hrDepId,
            'job_title_id' => $hrStaffTitleId,
            'code' => 'EMP00003',
            'full_name' => 'Nguyễn Thị Nhân Sự',
            'full_name_kana' => 'グエン・ティ・ニャン・ス',
            'romaji_name' => 'Nguyen Thi Nhan Su',
            'date_of_birth' => '1995-04-12',
            'gender' => 'FEMALE',
            'hometown' => 'Hà Nội, Việt Nam',
            'place_of_birth' => 'Hà Nội',
            'nationality' => 'Việt Nam',
            'ethnicity' => 'Kinh',
            'religion' => 'Không',
            'email' => 'hr@compliance.vn',
            'phone' => '0981112222',
            'password' => Hash::make('P@ssw0rd123'),
            'identity_type' => 'CCCD',
            'identity_number' => '001095005678',
            'address_registered' => 'Hà Nội',
            'address_current' => 'Hà Nội',
            'role' => 'HR',
            'status' => 'ACTIVE',
            'join_date' => '2025-03-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed Accountant Account
        DB::table('employees')->insert([
            'department_id' => $hrDepId,
            'job_title_id' => $hrStaffTitleId,
            'code' => 'EMP00004',
            'full_name' => 'Phạm Văn Kế Toán',
            'full_name_kana' => 'ファム・ヴァン・ケ・トゥアン',
            'romaji_name' => 'Pham Van Ke Toan',
            'date_of_birth' => '1992-08-25',
            'gender' => 'MALE',
            'hometown' => 'Hải Phòng, Việt Nam',
            'place_of_birth' => 'Hải Phòng',
            'nationality' => 'Việt Nam',
            'ethnicity' => 'Kinh',
            'religion' => 'Không',
            'email' => 'accountant@compliance.vn',
            'phone' => '0983334444',
            'password' => Hash::make('P@ssw0rd123'),
            'identity_type' => 'CCCD',
            'identity_number' => '001092008765',
            'address_registered' => 'Hải Phòng',
            'address_current' => 'Hà Nội',
            'role' => 'ACCOUNTANT',
            'status' => 'ACTIVE',
            'join_date' => '2025-03-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed fixed Staff Account
        DB::table('employees')->insert([
            'department_id' => $itDepId,
            'job_title_id' => $itSeniorTitleId,
            'code' => 'EMP00005',
            'full_name' => 'Lê Văn Nhân Viên',
            'full_name_kana' => 'レ・ヴァン・ニャン・ヴィエン',
            'romaji_name' => 'Le Van Nhan Vien',
            'date_of_birth' => '1998-11-30',
            'gender' => 'MALE',
            'hometown' => 'Thanh Hóa, Việt Nam',
            'place_of_birth' => 'Thanh Hóa',
            'nationality' => 'Việt Nam',
            'ethnicity' => 'Kinh',
            'religion' => 'Không',
            'email' => 'staff@compliance.vn',
            'phone' => '0985556666',
            'password' => Hash::make('P@ssw0rd123'),
            'identity_type' => 'CCCD',
            'identity_number' => '001098004321',
            'address_registered' => 'Thanh Hóa',
            'address_current' => 'Hà Nội',
            'role' => 'STAFF',
            'status' => 'ACTIVE',
            'join_date' => '2025-04-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Seed 20 additional employees for testing
        $firstNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];
        $middleNames = ['Văn', 'Thị', 'Minh', 'Anh', 'Khánh', 'Đức', 'Hồng', 'Hoàng', 'Thanh', 'Ngọc', 'Tuấn', 'Quang'];
        $lastNames = ['Sơn', 'Hải', 'Huy', 'Tùng', 'Nam', 'Trang', 'Vy', 'Linh', 'Hương', 'Hùng', 'Cường', 'Dũng', 'Phong', 'Bình', 'An', 'Khôi'];

        for ($i = 6; $i <= 25; $i++) {
            $code = sprintf('EMP%05d', $i);
            $gender = $i % 2 === 0 ? 'FEMALE' : 'MALE';
            
            $fn = $firstNames[array_rand($firstNames)];
            $mn = $middleNames[array_rand($middleNames)];
            $ln = $lastNames[array_rand($lastNames)];
            $fullName = "$fn $mn $ln";
            
            // Basic Romaji conversion
            $cleanName = str_replace(
                ['á','à','ả','ã','ạ','ă','ắ','ằ','ẳ','ẵ','ặ','â','ấ','ần','ẩ','ẫ','ậ','é','è','ẻ','ẽ','ẹ','ê','ế','ề','ể','ễ','ệ','í','ì','ỉ','ĩ','ị','ó','ò','ỏ','õ','ọ','ô','ố','ồ','ổ','ỗ','ộ','ơ','ớ','ờ','ở','ỡ','ợ','ú','ù','ủ','ũ','ụ','ư','ứ','ừ','ử','ữ','ự','ý','ỳ','ỷ','ỹ','ỵ','đ',' ', '’', '`'],
                ['a','a','a','a','a','a','a','a','a','a','a','a','a','an','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d',' ', '', ''],
                mb_strtolower($fullName)
            );
            $romaji = ucwords($cleanName);

            $isIT = $i % 2 === 1;
            $depId = $isIT ? $itDepId : $hrDepId;
            $titleId = $isIT ? $itSeniorTitleId : $hrStaffTitleId;

            DB::table('employees')->insert([
                'department_id' => $depId,
                'job_title_id' => $titleId,
                'code' => $code,
                'full_name' => $fullName,
                'full_name_kana' => 'テスト・ユーザー',
                'romaji_name' => $romaji,
                'date_of_birth' => sprintf('%04d-%02d-%02d', rand(1985, 2002), rand(1, 12), rand(1, 28)),
                'gender' => $gender,
                'hometown' => 'Hà Nội, Việt Nam',
                'place_of_birth' => 'Hà Nội',
                'nationality' => 'Việt Nam',
                'ethnicity' => 'Kinh',
                'religion' => 'Không',
                'email' => strtolower(str_replace(' ', '', $romaji)) . "@compliance.vn",
                'phone' => sprintf('09%08d', rand(10000000, 99999999)),
                'password' => Hash::make('P@ssw0rd123'),
                'identity_type' => 'CCCD',
                'identity_number' => sprintf('00109%07d', rand(1000000, 9999999)),
                'address_registered' => 'Địa chỉ đăng ký hộ khẩu thường trú của nhân viên ' . $code,
                'address_current' => 'Địa chỉ tạm trú hiện tại của nhân viên ' . $code,
                'role' => 'STAFF',
                'status' => 'ACTIVE',
                'join_date' => '2025-03-01',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
