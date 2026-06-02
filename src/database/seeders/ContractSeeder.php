<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        // Clean existing records to prevent unique constraints duplicate error
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('contracts')->truncate();
        DB::table('t_documents')->truncate(); // Clean up documents related to contracts/employees
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get employee IDs
        $empAdmin = DB::table('employees')->where('code', 'EMP00001')->first();
        $empManager = DB::table('employees')->where('code', 'EMP00002')->first();

        // 1. Labor Contract for Admin (Nguyễn Văn Quản Trị)
        if ($empAdmin) {
            DB::table('contracts')->insert([
                'employee_id' => $empAdmin->id,
                'contract_code' => 'HDLD-2025-0001',
                'type' => 'LABOR',
                'employment_type' => 'SEISHAIN',
                'job_title' => 'Trưởng phòng Công nghệ Thông tin (IT Leader)',
                'work_location' => 'VP Keangnam Hà Nội',
                'working_hours_per_day' => 8.00,
                'probation_salary_percentage' => 85,
                'bank_name' => 'Vietcombank',
                'bank_account_number' => '1029384756',
                'insurance_enrolled' => 'BHXH, BHYT, BHTN bắt buộc',
                'is_36_agreement_applicable' => true,
                'overtime_allowance_included' => true,
                'included_overtime_hours' => 30,
                'probation_period_months' => 2,
                'sign_date' => '2025-01-01',
                'start_date' => '2025-01-01',
                'end_date' => null, // Vô thời hạn
                'value' => 35000000,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Labor Contract for Manager (Trần Thị Nhân Sự)
        if ($empManager) {
            DB::table('contracts')->insert([
                'employee_id' => $empManager->id,
                'contract_code' => 'HDLD-2025-0002',
                'type' => 'LABOR',
                'employment_type' => 'KEIYAKUSHAIN',
                'job_title' => 'Trưởng bộ phận Hành chính Nhân sự',
                'work_location' => 'VP Keangnam Hà Nội',
                'working_hours_per_day' => 8.00,
                'probation_salary_percentage' => 85,
                'bank_name' => 'Techcombank',
                'bank_account_number' => '9876543210',
                'insurance_enrolled' => 'BHXH, BHYT đầy đủ',
                'is_36_agreement_applicable' => true,
                'overtime_allowance_included' => false,
                'included_overtime_hours' => 0,
                'probation_period_months' => 2,
                'sign_date' => '2025-02-15',
                'start_date' => '2025-02-15',
                'end_date' => '2027-02-14',
                'value' => 25000000,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Vendor Contract (Hợp đồng Thầu phụ Thương mại)
        DB::table('contracts')->insert([
            'employee_id' => null,
            'contract_code' => 'HDTP-2026-0001',
            'type' => 'VENDOR',
            'partner_name' => 'Công ty TNHH Giải pháp Phần mềm FPT',
            'partner_tax_code' => '0101243156',
            'partner_representative' => 'Nguyễn Thế Phương',
            'partner_representative_role' => 'Giám đốc Chi nhánh',
            'partner_address' => 'Tòa nhà FPT, Phố Duy Tân, Dịch Vọng Hậu, Cầu Giấy, Hà Nội',
            'payment_method' => 'Chuyển khoản ngân hàng (BANK_TRANSFER)',
            'payment_terms' => 'Thanh toán theo tiến độ 3 đợt nghiệm thu bàn giao',
            'billing_cycle' => 'Theo quý (QUARTERLY)',
            'sign_date' => '2026-03-01',
            'start_date' => '2026-03-01',
            'end_date' => '2027-02-28',
            'value' => 500000000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Client Contract (Hợp đồng Khách hàng / Cung cấp dịch vụ)
        DB::table('contracts')->insert([
            'employee_id' => null,
            'contract_code' => 'HDDV-2026-0002',
            'type' => 'CLIENT',
            'partner_name' => 'Tập đoàn Viễn thông Quân đội Viettel',
            'partner_tax_code' => '0100109106',
            'partner_representative' => 'Tào Đức Thắng',
            'partner_representative_role' => 'Chủ tịch kiêm Tổng giám đốc',
            'partner_address' => 'Lô D26, Khu đô thị mới Cầu Giấy, Yên Hòa, Cầu Giấy, Hà Nội',
            'payment_method' => 'Chuyển khoản ngân hàng (BANK_TRANSFER)',
            'payment_terms' => 'Thanh toán 100% trong vòng 15 ngày kể từ ngày nghiệm thu',
            'billing_cycle' => 'Thanh toán 1 lần (ONE_TIME)',
            'sign_date' => '2026-05-15',
            'start_date' => '2026-05-15',
            'end_date' => '2027-05-14',
            'value' => 120000000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
