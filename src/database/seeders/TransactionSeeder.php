<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clean existing records in t_transactions (due to cascade on t_documents)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('t_transactions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Seeding financial transactions for 2024, 2025, and 2026...');

        $transactions = [];
        $index = 1;

        // Years to seed
        $years = [2024, 2025, 2026];

        foreach ($years as $year) {
            // Maximum month to seed in 2026 is June (current mock date is June 2026)
            $maxMonth = ($year === 2026) ? 6 : 12;

            for ($month = 1; $month <= $maxMonth; $month++) {
                $monthStr = sprintf('%02d', $month);

                // --- A. REPETITIVE MONTHLY EXPENSES ---
                
                // 1. Office Rent (EXPENSE)
                // Rent increases slightly per year
                $rentAmount = 25000000;
                if ($year === 2025) $rentAmount = 27000000;
                if ($year === 2026) $rentAmount = 30000000;
                
                $rentTax = $rentAmount * 0.10; // 10% VAT
                $rentNet = $rentAmount - $rentTax;
                $rentDate = "$year-$monthStr-05";

                $transactions[] = [
                    'code' => sprintf('TX-%s%s05-%04d', $year, $monthStr, $index++),
                    'type' => 'EXPENSE',
                    'amount' => $rentAmount,
                    'net_amount' => $rentNet,
                    'tax_amount' => $rentTax,
                    'tax_rate_type' => 'VAT_10',
                    'invoice_registration_number' => sprintf('00%d%04d', $year % 100, rand(1000, 9999)),
                    'withholding_tax' => 0.00,
                    'payment_method' => 'BANK_TRANSFER',
                    'category' => 'Thuê văn phòng (Office Rent)',
                    'transaction_date' => $rentDate,
                    'description' => "Thanh toán tiền thuê mặt bằng văn phòng Keangnam - Tháng $month/$year",
                    'status' => 'PAID',
                    'created_by' => 'admin@compliance.vn',
                    'created_at' => Carbon::parse($rentDate)->addHours(9),
                    'updated_at' => Carbon::parse($rentDate)->addHours(9),
                ];

                // 2. Employee Salaries (EXPENSE)
                // Salaries paid on 5th of the NEXT month
                $salaryAmount = 75000000;
                if ($year === 2025) $salaryAmount = 82000000;
                if ($year === 2026) $salaryAmount = 95000000;

                // Withholding Personal Income Tax (PIT ~ 10% average total)
                $withholdingTax = $salaryAmount * 0.08; 
                $salaryDate = Carbon::parse("$year-$monthStr-01")->addMonth()->setUnit('day', 5)->format('Y-m-d');
                
                // Skip if salary payment falls after our mock time (June 2026 salary paid in July)
                if (Carbon::parse($salaryDate)->isBefore(Carbon::parse('2026-06-30'))) {
                    $transactions[] = [
                        'code' => sprintf('TX-%s-%04d', str_replace('-', '', $salaryDate), $index++),
                        'type' => 'EXPENSE',
                        'amount' => $salaryAmount,
                        'net_amount' => $salaryAmount, // Salaries have no VAT
                        'tax_amount' => 0.00,
                        'tax_rate_type' => 'NONE',
                        'invoice_registration_number' => null,
                        'withholding_tax' => $withholdingTax,
                        'payment_method' => 'BANK_TRANSFER',
                        'category' => 'Lương & Phúc lợi (Salary & Benefits)',
                        'transaction_date' => $salaryDate,
                        'description' => "Chi trả lương và phụ cấp bảo hiểm nhân sự toàn công ty - Kỳ lương Tháng $month/$year",
                        'status' => 'PAID',
                        'created_by' => 'admin@compliance.vn',
                        'created_at' => Carbon::parse($salaryDate)->addHours(10),
                        'updated_at' => Carbon::parse($salaryDate)->addHours(10),
                    ];
                }

                // 3. IT Cloud Services & Infrastructure (EXPENSE)
                // AWS, Azure, Google Cloud expenses
                $cloudAmount = rand(6000000, 10000000);
                $cloudTax = $cloudAmount * 0.10; // 10% VAT
                $cloudNet = $cloudAmount - $cloudTax;
                $cloudDate = "$year-$monthStr-15";

                $transactions[] = [
                    'code' => sprintf('TX-%s%s15-%04d', $year, $monthStr, $index++),
                    'type' => 'EXPENSE',
                    'amount' => $cloudAmount,
                    'net_amount' => $cloudNet,
                    'tax_amount' => $cloudTax,
                    'tax_rate_type' => 'VAT_10',
                    'invoice_registration_number' => sprintf('AWS-%s-%04d', $year, rand(100, 999)),
                    'withholding_tax' => 0.00,
                    'payment_method' => 'CREDIT_CARD',
                    'category' => 'Dịch vụ Đám mây & Internet (IT Infrastructure)',
                    'transaction_date' => $cloudDate,
                    'description' => "Thanh toán cước phí máy chủ AWS EC2, S3 và mạng CDN - Tháng $month/$year",
                    'status' => 'PAID',
                    'created_by' => 'admin@compliance.vn',
                    'created_at' => Carbon::parse($cloudDate)->addHours(16),
                    'updated_at' => Carbon::parse($cloudDate)->addHours(16),
                ];

                // --- B. REPETITIVE REVENUES ---
                
                // 1. Client Retainer / Service Contract (REVENUE)
                // Recurrent revenue from a major client on the 25th
                $revenueAmount = 140000000;
                if ($year === 2025) $revenueAmount = 160000000;
                if ($year === 2026) $revenueAmount = 185000000;

                // Alternate tax types to look realistic (8% VAT, 10% VAT)
                $vatRate = ($month % 2 === 0) ? 0.08 : 0.10;
                $vatRateType = ($month % 2 === 0) ? 'VAT_8' : 'VAT_10';
                $revenueTax = $revenueAmount * $vatRate;
                $revenueNet = $revenueAmount - $revenueTax;
                $revenueDate = "$year-$monthStr-25";

                $transactions[] = [
                    'code' => sprintf('TX-%s%s25-%04d', $year, $monthStr, $index++),
                    'type' => 'REVENUE',
                    'amount' => $revenueAmount,
                    'net_amount' => $revenueNet,
                    'tax_amount' => $revenueTax,
                    'tax_rate_type' => $vatRateType,
                    'invoice_registration_number' => sprintf('HDDV-%s-%04d', $year, rand(1000, 9999)),
                    'withholding_tax' => 0.00,
                    'payment_method' => 'BANK_TRANSFER',
                    'category' => 'Hợp đồng dịch vụ (Service Contract)',
                    'transaction_date' => $revenueDate,
                    'description' => "Nghiệm thu thanh toán dịch vụ hỗ trợ kiểm soát tuân thủ định kỳ - Tháng $month/$year",
                    'status' => 'PAID',
                    'created_by' => 'admin@compliance.vn',
                    'created_at' => Carbon::parse($revenueDate)->addHours(14),
                    'updated_at' => Carbon::parse($revenueDate)->addHours(14),
                ];

                // --- C. SPORADIC / ONE-OFF TRANSACTIONS ---
                
                // Quarter-end bonus revenue or specialized consulting contract
                if ($month % 3 === 0) {
                    $consultingAmount = rand(50000000, 90000000);
                    $consultingTax = $consultingAmount * 0.10;
                    $consultingNet = $consultingAmount - $consultingTax;
                    $consultingDate = "$year-$monthStr-28";

                    $transactions[] = [
                        'code' => sprintf('TX-%s%s28-%04d', $year, $monthStr, $index++),
                        'type' => 'REVENUE',
                        'amount' => $consultingAmount,
                        'net_amount' => $consultingNet,
                        'tax_amount' => $consultingTax,
                        'tax_rate_type' => 'VAT_10',
                        'invoice_registration_number' => sprintf('HDKH-%s-%04d', $year, rand(100, 999)),
                        'withholding_tax' => 0.00,
                        'payment_method' => 'BANK_TRANSFER',
                        'category' => 'Tư vấn Chuyên sâu (Premium Consulting)',
                        'transaction_date' => $consultingDate,
                        'description' => "Thanh toán dịch vụ Tư vấn & Đánh giá rủi ro Tuân thủ hệ thống Quý " . ($month / 3),
                        'status' => 'PAID',
                        'created_by' => 'admin@compliance.vn',
                        'created_at' => Carbon::parse($consultingDate)->addHours(11),
                        'updated_at' => Carbon::parse($consultingDate)->addHours(11),
                    ];
                }
            }

            // Year-end Bonuses & Hardware Purchase
            if ($year < 2026) {
                // IT Equipment purchase in January
                $equipAmount = 45000000;
                $equipTax = $equipAmount * 0.10;
                $equipNet = $equipAmount - $equipTax;
                $equipDate = "$year-01-20";

                $transactions[] = [
                    'code' => sprintf('TX-%s0120-%04d', $year, $index++),
                    'type' => 'EXPENSE',
                    'amount' => $equipAmount,
                    'net_amount' => $equipNet,
                    'tax_amount' => $equipTax,
                    'tax_rate_type' => 'VAT_10',
                    'invoice_registration_number' => sprintf('HDMH-%s-%03d', $year, rand(10, 99)),
                    'withholding_tax' => 0.00,
                    'payment_method' => 'BANK_TRANSFER',
                    'category' => 'Thiết bị IT & Văn phòng (IT Equipment)',
                    'transaction_date' => $equipDate,
                    'description' => "Mua sắm nâng cấp dàn máy tính Macbook Air M2 cho phòng phát triển phần mềm năm $year",
                    'status' => 'PAID',
                    'created_by' => 'admin@compliance.vn',
                    'created_at' => Carbon::parse($equipDate)->addHours(14),
                    'updated_at' => Carbon::parse($equipDate)->addHours(14),
                ];

                // Year-end bonus paid in December
                $bonusAmount = 60000000;
                $bonusDate = "$year-12-28";
                $bonusPIT = $bonusAmount * 0.10;

                $transactions[] = [
                    'code' => sprintf('TX-%s1228-%04d', $year, $index++),
                    'type' => 'EXPENSE',
                    'amount' => $bonusAmount,
                    'net_amount' => $bonusAmount,
                    'tax_amount' => 0.00,
                    'tax_rate_type' => 'NONE',
                    'invoice_registration_number' => null,
                    'withholding_tax' => $bonusPIT,
                    'payment_method' => 'BANK_TRANSFER',
                    'category' => 'Lương & Phúc lợi (Salary & Benefits)',
                    'transaction_date' => $bonusDate,
                    'description' => "Thưởng Tết & Đánh giá hiệu suất cuối năm cho đội ngũ cán bộ nhân viên năm $year",
                    'status' => 'PAID',
                    'created_by' => 'admin@compliance.vn',
                    'created_at' => Carbon::parse($bonusDate)->addHours(17),
                    'updated_at' => Carbon::parse($bonusDate)->addHours(17),
                ];
            }
        }

        // 4. Batch insert into database for ultra performance
        $chunks = array_chunk($transactions, 50);
        foreach ($chunks as $chunk) {
            DB::table('t_transactions')->insert($chunk);
        }

        $this->command->info(sprintf('✅ Successfully seeded %d transactions for visual statistics!', count($transactions)));
    }
}
