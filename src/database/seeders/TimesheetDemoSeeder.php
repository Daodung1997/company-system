<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimesheetDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clean existing records in timesheets, leave_requests, configs, and compliance issues
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('timesheets')->truncate();
        DB::table('leave_requests')->truncate();
        DB::table('working_hour_configs')->truncate();
        DB::table('t_compliance_issues')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Setting up May 2026 (previous month) with abnormal & compliance violation data...');

        // 2. Get Employee IDs
        $emp1 = DB::table('employees')->where('code', 'EMP00001')->first();
        $emp2 = DB::table('employees')->where('code', 'EMP00002')->first();

        if (!$emp1 || !$emp2) {
            $this->command->error('Missing EMP00001 or EMP00002. Please seed CompanyEmployeeSeeder first.');
            return;
        }

        // Set EMP00002's Zairyu Card (Resident Card) to be expired to trigger a real compliance visa issue!
        DB::table('employees')->where('id', $emp2->id)->update([
            'zairyu_card_expiry' => '2026-05-15',
        ]);

        // 3. Create active working hour configurations for 2026
        DB::table('working_hour_configs')->insert([
            [
                'name' => 'Cấu hình chuẩn năm 2026',
                'start_time' => '08:30:00',
                'end_time' => '17:30:00',
                'is_default' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $timesheets = [];
        $leaveRequests = [];

        // Date range: from 2026-05-01 to 2026-05-31 (May 2026 - previous month)
        $startDate = Carbon::parse('2026-05-01');
        $endDate = Carbon::parse('2026-05-31');

        $currentDate = $startDate->copy();

        while ($currentDate->isBefore($endDate) || $currentDate->isSameDay($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek; // 0 = Sunday, 6 = Saturday

            // Skip Sundays completely
            if ($dayOfWeek !== Carbon::SUNDAY) {
                // Determine for Employee 1 (Nguyễn Văn Quản Trị)
                $rand1 = rand(1, 100);
                $checkIn1 = null;
                $checkOut1 = null;
                $status1 = 'PRESENT';
                $note1 = 'Chấm công hợp lệ';

                if ($dayOfWeek === Carbon::SATURDAY) {
                    // Saturdays: Half day (morning only)
                    $checkIn1 = "$dateStr 08:20:00";
                    $checkOut1 = "$dateStr 12:00:00";
                    $note1 = 'Làm việc sáng Thứ 7';
                } else {
                    // Weekdays
                    if ($dateStr === '2026-05-12') {
                        // ❌ Abnormal Record 1: Forgot checkout
                        $checkIn1 = "$dateStr 08:12:00";
                        $checkOut1 = null;
                        $note1 = 'Quên quét thẻ ra về (Forgot Check-out)';
                    } elseif ($dateStr === '2026-05-18') {
                        // ❌ Abnormal Record 2: Late check-in
                        $checkIn1 = "$dateStr 09:42:00"; // 72 mins late
                        $checkOut1 = "$dateStr 17:45:00";
                        $note1 = 'Đi muộn 72 phút do sự cố kẹt xe nghiêm trọng trên đường Vành Đai 3';
                    } elseif (in_array($dateStr, ['2026-05-20', '2026-05-21', '2026-05-22', '2026-05-25'])) {
                        // ⚠️ Overtime Violation: Working excessive hours
                        $checkIn1 = "$dateStr 08:15:00";
                        $overtimeHours = ['2026-05-20' => '22:15:00', '2026-05-21' => '23:05:00', '2026-05-22' => '22:45:00', '2026-05-25' => '23:30:00'][$dateStr];
                        $checkOut1 = "$dateStr $overtimeHours";
                        $note1 = 'Làm thêm giờ hoàn thành tiến độ bàn giao dự án';
                    } elseif ($dateStr === '2026-05-05') {
                        // Regular approved leave
                        $status1 = 'LEAVE';
                        $note1 = 'Nghỉ phép phép năm được duyệt';
                        $leaveRequests[] = [
                            'employee_id' => $emp1->id,
                            'leave_type' => 'ANNUAL',
                            'leave_session' => 'ALL',
                            'start_date' => $dateStr,
                            'end_date' => $dateStr,
                            'reason' => 'Nghỉ giải quyết công việc gia đình riêng',
                            'status' => 'APPROVED',
                            'approved_by' => $emp2->id,
                            'approved_at' => Carbon::parse($dateStr)->subDays(2)->addHours(14),
                            'approver_note' => 'Đã duyệt nghỉ phép năm.',
                            'created_at' => Carbon::parse($dateStr)->subDays(3),
                            'updated_at' => Carbon::parse($dateStr)->subDays(2),
                        ];
                    } else {
                        // Normal day
                        $randomMinIn = rand(5, 25);
                        $randomMinOut = rand(30, 45);
                        $checkIn1 = sprintf('%s 08:%02d:00', $dateStr, $randomMinIn);
                        $checkOut1 = sprintf('%s 17:%02d:00', $dateStr, $randomMinOut);
                    }
                }

                $timesheets[] = [
                    'employee_id' => $emp1->id,
                    'date' => $dateStr,
                    'check_in' => $checkIn1,
                    'check_out' => $checkOut1,
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'status' => $status1,
                    'note' => $note1,
                    'created_at' => Carbon::parse($dateStr)->addHours(19),
                    'updated_at' => Carbon::parse($dateStr)->addHours(19),
                ];

                // Determine for Employee 2 (Trần Thị Nhân Sự)
                $checkIn2 = null;
                $checkOut2 = null;
                $status2 = 'PRESENT';
                $note2 = 'Chấm công hợp lệ';

                if ($dayOfWeek === Carbon::SATURDAY) {
                    $checkIn2 = "$dateStr 08:22:00";
                    $checkOut2 = "$dateStr 12:00:00";
                    $note2 = 'Làm việc sáng Thứ 7';
                } else {
                    // Weekdays
                    if ($dateStr === '2026-05-08') {
                        // ❌ Abnormal Record 3: Absent without leave
                        $status2 = 'ABSENT';
                        $note2 = 'Nghỉ tự ý không phép, không liên lạc được qua điện thoại cá nhân';
                    } elseif ($dateStr === '2026-05-21') {
                        // ❌ Abnormal Record 4: Early checkout without approval
                        $checkIn2 = "$dateStr 08:22:00";
                        $checkOut2 = "$dateStr 15:45:00"; // Left early
                        $note2 = 'Xin về sớm đi giải quyết việc gia đình khẩn cấp nhưng chưa được phê duyệt qua hệ thống';
                    } elseif ($dateStr === '2026-05-26') {
                        // ❌ Abnormal Record 5: Late check-in
                        $checkIn2 = "$dateStr 10:15:00"; // 105 mins late
                        $checkOut2 = "$dateStr 17:35:00";
                        $note2 = 'Đi muộn 105 phút - Lý do ngủ quên';
                    } elseif ($dateStr === '2026-05-14') {
                        // ❌ Process Violation: Overlapping leave requests
                        $status2 = 'LEAVE';
                        $note2 = 'Nghỉ phép phép năm trọn ngày được duyệt';

                        // 1. First Leave Request: Approved for ALL day
                        $leaveRequests[] = [
                            'employee_id' => $emp2->id,
                            'leave_type' => 'ANNUAL',
                            'leave_session' => 'ALL',
                            'start_date' => $dateStr,
                            'end_date' => $dateStr,
                            'reason' => 'Nghỉ phép đi khám sức khỏe định kỳ ở bệnh viện',
                            'status' => 'APPROVED',
                            'approved_by' => $emp1->id,
                            'approved_at' => Carbon::parse($dateStr)->subDays(1)->addHours(10),
                            'approver_note' => 'Đã phê duyệt nghỉ trọn ngày.',
                            'created_at' => Carbon::parse($dateStr)->subDays(2),
                            'updated_at' => Carbon::parse($dateStr)->subDays(1),
                        ];

                        // 2. Second Leave Request: Concurrently pending for the SAME day (Overlapping!)
                        $leaveRequests[] = [
                            'employee_id' => $emp2->id,
                            'leave_type' => 'ANNUAL',
                            'leave_session' => 'MORNING',
                            'start_date' => $dateStr,
                            'end_date' => $dateStr,
                            'reason' => 'Nghỉ phép buổi sáng đi làm thủ tục hành chính',
                            'status' => 'PENDING',
                            'approved_by' => null,
                            'approved_at' => null,
                            'approver_note' => null,
                            'created_at' => Carbon::parse($dateStr)->subMinutes(30),
                            'updated_at' => Carbon::parse($dateStr)->subMinutes(30),
                        ];
                    } elseif ($dateStr === '2026-05-28') {
                        // ❌ Abnormal Record 6: Rejected Leave Request
                        $randomMinIn = rand(5, 25);
                        $randomMinOut = rand(30, 45);
                        $checkIn2 = sprintf('%s 08:%02d:00', $dateStr, $randomMinIn);
                        $checkOut2 = sprintf('%s 17:%02d:00', $dateStr, $randomMinOut);
                        $note2 = 'Đi làm bình thường (Đơn xin nghỉ bị từ chối)';

                        $leaveRequests[] = [
                            'employee_id' => $emp2->id,
                            'leave_type' => 'ANNUAL',
                            'leave_session' => 'ALL',
                            'start_date' => $dateStr,
                            'end_date' => $dateStr,
                            'reason' => 'Có việc cá nhân khẩn cấp',
                            'status' => 'REJECTED',
                            'approved_by' => $emp1->id,
                            'approved_at' => Carbon::parse($dateStr)->addHours(9),
                            'approver_note' => 'Không duyệt do gửi đơn quá muộn sát giờ làm việc và dự án đang chạy.',
                            'created_at' => Carbon::parse($dateStr)->addHours(8),
                            'updated_at' => Carbon::parse($dateStr)->addHours(9),
                        ];
                    } else {
                        // Normal day
                        $randomMinIn = rand(5, 25);
                        $randomMinOut = rand(30, 45);
                        $checkIn2 = sprintf('%s 08:%02d:00', $dateStr, $randomMinIn);
                        $checkOut2 = sprintf('%s 17:%02d:00', $dateStr, $randomMinOut);
                    }
                }

                $timesheets[] = [
                    'employee_id' => $emp2->id,
                    'date' => $dateStr,
                    'check_in' => $checkIn2,
                    'check_out' => $checkOut2,
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'status' => $status2,
                    'note' => $note2,
                    'created_at' => Carbon::parse($dateStr)->addHours(19),
                    'updated_at' => Carbon::parse($dateStr)->addHours(19),
                ];
            }

            $currentDate->addDay();
        }

        // Batch insert timesheets
        $timesheetChunks = array_chunk($timesheets, 100);
        foreach ($timesheetChunks as $chunk) {
            DB::table('timesheets')->insert($chunk);
        }

        // Batch insert leave requests
        $leaveChunks = array_chunk($leaveRequests, 50);
        foreach ($leaveChunks as $chunk) {
            DB::table('leave_requests')->insert($chunk);
        }

        // 4. Seed Compliance Issues (Abnormal Compliance Violations)
        $contract = DB::table('contracts')->where('contract_code', 'HDDV-2026-0002')->first();
        
        DB::table('t_compliance_issues')->insert([
            [
                'employee_id' => $emp1->id,
                'contract_id' => null,
                'transaction_id' => null,
                'issue_type' => 'OVERTIME_LIMIT',
                'severity' => 'CRITICAL',
                'description' => 'Nhân viên Nguyễn Văn Quản Trị đã vượt quá giới hạn giờ làm thêm trong tháng 05/2026 (Đã làm 52.5 giờ thêm, vượt quá giới hạn 40 giờ/tháng theo Luật Lao động Việt Nam).',
                'status' => 'OPEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_id' => $emp2->id,
                'contract_id' => null,
                'transaction_id' => null,
                'issue_type' => 'VISA_EXPIRATION',
                'severity' => 'CRITICAL',
                'description' => 'Visa hoặc Giấy phép lao động của nhân viên Trần Thị Nhân Sự đã hết hạn vào ngày 15/05/2026. Cần cập nhật giấy phép mới hoặc làm thủ tục gia hạn khẩn cấp để tránh vi phạm nghiêm trọng luật pháp lao động Việt Nam.',
                'status' => 'OPEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_id' => null,
                'contract_id' => $contract ? $contract->id : null,
                'transaction_id' => null,
                'issue_type' => 'MISSING_INVOICE',
                'severity' => 'WARNING',
                'description' => 'Hợp đồng dịch vụ HDDV-2026-0002 với Tập đoàn Viễn thông Quân đội Viettel ký ngày 15/05/2026 hiện đang thiếu hóa đơn đính kèm của đợt thanh toán đầu tiên.',
                'status' => 'OPEN',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $this->command->info(sprintf('✅ Seeded %d timesheet records for May 2026!', count($timesheets)));
        $this->command->info(sprintf('✅ Seeded %d leave requests (with overlaps and rejections)!', count($leaveRequests)));
        $this->command->info('✅ Seeded 3 critical compliance issues into the system!');
    }
}
