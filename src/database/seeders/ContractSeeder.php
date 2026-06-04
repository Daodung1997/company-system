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
                'company_name' => 'Công ty Cổ phần Giải pháp Công nghệ Việt Nam',
                'company_tax_code' => '0109283746',
                'company_address' => 'Tòa nhà Keangnam Landmark 72, Đường Phạm Hùng, Phường Mễ Trì, Quận Nam Từ Liêm, Hà Nội',
                'company_representative' => 'Nguyễn Văn A',
                'company_representative_role' => 'Tổng Giám đốc',
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
                'company_name' => 'Công ty Cổ phần Giải pháp Công nghệ Việt Nam',
                'company_tax_code' => '0109283746',
                'company_address' => 'Tòa nhà Keangnam Landmark 72, Đường Phạm Hùng, Phường Mễ Trì, Quận Nam Từ Liêm, Hà Nội',
                'company_representative' => 'Nguyễn Văn A',
                'company_representative_role' => 'Tổng Giám đốc',
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
            'company_name' => 'Công ty Cổ phần Giải pháp Công nghệ Việt Nam',
            'company_tax_code' => '0109283746',
            'company_address' => 'Tòa nhà Keangnam Landmark 72, Đường Phạm Hùng, Phường Mễ Trì, Quận Nam Từ Liêm, Hà Nội',
            'company_representative' => 'Nguyễn Văn A',
            'company_representative_role' => 'Tổng Giám đốc',
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
            'company_name' => 'Công ty Cổ phần Giải pháp Công nghệ Việt Nam',
            'company_tax_code' => '0109283746',
            'company_address' => 'Tòa nhà Keangnam Landmark 72, Đường Phạm Hùng, Phường Mễ Trì, Quận Nam Từ Liêm, Hà Nội',
            'company_representative' => 'Nguyễn Văn A',
            'company_representative_role' => 'Tổng Giám đốc',
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

        // Seed mock documents in t_documents
        $contract1 = DB::table('contracts')->where('contract_code', 'HDLD-2025-0001')->first();
        $contract2 = DB::table('contracts')->where('contract_code', 'HDLD-2025-0002')->first();
        $contract3 = DB::table('contracts')->where('contract_code', 'HDTP-2026-0001')->first();

        // Ensure storage directory and files exist
        \Illuminate\Support\Facades\Storage::disk('public')->put('documents/hop_dong_lao_dong_admin.pdf', 'Dummy PDF Content for Admin Contract');
        \Illuminate\Support\Facades\Storage::disk('public')->put('documents/cccd_nguyen_van_admin.jpg', 'Dummy Image Content for Admin ID Card');
        \Illuminate\Support\Facades\Storage::disk('public')->put('documents/hop_dong_lao_dong_manager.pdf', 'Dummy PDF Content for Manager Contract');
        \Illuminate\Support\Facades\Storage::disk('public')->put('documents/cv_tran_thi_nhan_su.pdf', 'Dummy PDF Content for Manager CV');
        \Illuminate\Support\Facades\Storage::disk('public')->put('documents/hop_dong_thau_phu_fpt_signed.pdf', 'Dummy PDF Content for FPT Vendor Contract');

        if ($contract1 && $empAdmin) {
            DB::table('t_documents')->insert([
                [
                    'code' => 'DOC00001',
                    'origin_name' => 'hop_dong_lao_dong_admin.pdf',
                    'file_path' => 'documents/hop_dong_lao_dong_admin.pdf',
                    'disk' => 'public',
                    'extension' => 'pdf',
                    'filesize' => 102400,
                    'documentable_id' => $contract1->id,
                    'documentable_type' => 'App\\Models\\Contract',
                    'employee_id' => $empAdmin->id,
                    'contract_id' => $contract1->id,
                    'transaction_id' => null,
                    'status' => 'ACTIVE',
                    'created_by' => 'SYSTEM',
                    'updated_by' => 'SYSTEM',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'DOC00002',
                    'origin_name' => 'cccd_nguyen_van_admin.jpg',
                    'file_path' => 'documents/cccd_nguyen_van_admin.jpg',
                    'disk' => 'public',
                    'extension' => 'jpg',
                    'filesize' => 204800,
                    'documentable_id' => $empAdmin->id,
                    'documentable_type' => 'App\\Models\\Employee',
                    'employee_id' => $empAdmin->id,
                    'contract_id' => null,
                    'transaction_id' => null,
                    'status' => 'ACTIVE',
                    'created_by' => 'SYSTEM',
                    'updated_by' => 'SYSTEM',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }

        if ($contract2 && $empManager) {
            DB::table('t_documents')->insert([
                [
                    'code' => 'DOC00003',
                    'origin_name' => 'hop_dong_lao_dong_manager.pdf',
                    'file_path' => 'documents/hop_dong_lao_dong_manager.pdf',
                    'disk' => 'public',
                    'extension' => 'pdf',
                    'filesize' => 98000,
                    'documentable_id' => $contract2->id,
                    'documentable_type' => 'App\\Models\\Contract',
                    'employee_id' => $empManager->id,
                    'contract_id' => $contract2->id,
                    'transaction_id' => null,
                    'status' => 'ACTIVE',
                    'created_by' => 'SYSTEM',
                    'updated_by' => 'SYSTEM',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'DOC00004',
                    'origin_name' => 'cv_tran_thi_nhan_su.pdf',
                    'file_path' => 'documents/cv_tran_thi_nhan_su.pdf',
                    'disk' => 'public',
                    'extension' => 'pdf',
                    'filesize' => 150000,
                    'documentable_id' => $empManager->id,
                    'documentable_type' => 'App\\Models\\Employee',
                    'employee_id' => $empManager->id,
                    'contract_id' => null,
                    'transaction_id' => null,
                    'status' => 'ACTIVE',
                    'created_by' => 'SYSTEM',
                    'updated_by' => 'SYSTEM',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }

        if ($contract3) {
            DB::table('t_documents')->insert([
                [
                    'code' => 'DOC00005',
                    'origin_name' => 'hop_dong_thau_phu_fpt_signed.pdf',
                    'file_path' => 'documents/hop_dong_thau_phu_fpt_signed.pdf',
                    'disk' => 'public',
                    'extension' => 'pdf',
                    'filesize' => 450000,
                    'documentable_id' => $contract3->id,
                    'documentable_type' => 'App\\Models\\Contract',
                    'employee_id' => null,
                    'contract_id' => $contract3->id,
                    'transaction_id' => null,
                    'status' => 'ACTIVE',
                    'created_by' => 'SYSTEM',
                    'updated_by' => 'SYSTEM',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
}
