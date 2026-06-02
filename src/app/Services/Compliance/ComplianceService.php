<?php

namespace App\Services\Compliance;

use App\Models\ComplianceIssue;
use App\Models\Employee;
use App\Models\Contract;
use App\Models\Transaction;
use App\Models\Timesheet;
use App\Repositories\Compliance\ComplianceRepository;
use App\Services\AbstractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ComplianceService extends AbstractService
{
    public function __construct(protected ComplianceRepository $complianceRepository) {}

    /**
     * Get list of issues for a company with filters.
     */
    public function list(array $filters)
    {
        $employee = auth('api')->user();
        if (!$employee) {
            return [];
        }
        return $this->complianceRepository->getIssues($filters);
    }

    /**
     * Resolve a compliance issue manually.
     */
    public function resolveIssue(int $id, string $note = '')
    {
        $employee = auth('api')->user();
        if (!$employee) {
            return null;
        }

        $issue = $this->complianceRepository->find($id);
        if (!$issue) {
            return null;
        }

        return $this->complianceRepository->update($id, [
            'status' => 'RESOLVED',
            'resolved_at' => Carbon::now(),
            'resolved_by' => $employee->full_name . ($note ? " ($note)" : ""),
        ]);
    }

    /**
     * Run compliance scan for the system.
     */
    public function runScan(): array
    {
        $results = [
            'visa_scanned' => 0,
            'visa_issues_created' => 0,
            'contract_scanned' => 0,
            'contract_issues_created' => 0,
            'invoice_scanned' => 0,
            'invoice_issues_created' => 0,
            'ot_scanned' => 0,
            'ot_issues_created' => 0,
            'resolved_count' => 0,
        ];

        $this->beginTransaction();
        try {
            $now = Carbon::now();

            // ----------------------------------------------------
            // RULE 1: VISA EXPIRATION (ZAIRYU CARD EXPIRY)
            // ----------------------------------------------------
            $employees = Employee::all();
            foreach ($employees as $employee) {
                $results['visa_scanned']++;
                if ($employee->zairyu_card_expiry) {
                    $expiry = Carbon::parse($employee->zairyu_card_expiry);
                    $daysLeft = $now->diffInDays($expiry, false);

                    if ($daysLeft <= 90) {
                        $severity = 'INFO';
                        if ($daysLeft <= 30) {
                            $severity = 'CRITICAL';
                        } elseif ($daysLeft <= 60) {
                            $severity = 'WARNING';
                        }

                        $desc = "Thẻ ngoại kiều (Zairyu Card) của nhân viên {$employee->full_name} ({$employee->code}) " . 
                                ($daysLeft <= 0 ? "đã hết hạn vào ngày {$expiry->format('Y-m-d')}." : "sắp hết hạn trong {$daysLeft} ngày (Hạn dùng: {$expiry->format('Y-m-d')}).") . 
                                " Vui lòng liên hệ nhân sự để gia hạn gấp.";

                        $this->createOrUpdateIssue([
                            'employee_id' => $employee->id,
                            'issue_type' => 'VISA_EXPIRATION',
                            'severity' => $severity,
                            'description' => $desc,
                        ]);
                        $results['visa_issues_created']++;
                    } else {
                        // Visa is safe, self-heal existing issue
                        $resolved = $this->selfHealIssue('VISA_EXPIRATION', 'employee_id', $employee->id);
                        if ($resolved) $results['resolved_count']++;
                    }
                }
            }

            // ----------------------------------------------------
            // RULE 2: CONTRACT EXPIRATION
            // ----------------------------------------------------
            $contracts = Contract::where('status', 'ACTIVE')->get();
            foreach ($contracts as $contract) {
                $results['contract_scanned']++;
                if ($contract->end_date) {
                    $endDate = Carbon::parse($contract->end_date);
                    $daysLeft = $now->diffInDays($endDate, false);

                    if ($daysLeft <= 30) {
                        $severity = 'INFO';
                        if ($daysLeft <= 0) {
                            $severity = 'CRITICAL';
                        } elseif ($daysLeft <= 15) {
                            $severity = 'WARNING';
                        }

                        $targetName = $contract->employee ? $contract->employee->full_name : $contract->partner_name;
                        $desc = "Hợp đồng {$contract->contract_code} ({$contract->type}) của {$targetName} " . 
                                ($daysLeft <= 0 ? "đã hết hạn vào ngày {$endDate->format('Y-m-d')}." : "sắp hết hạn trong {$daysLeft} ngày (Ngày hết hạn: {$endDate->format('Y-m-d')}).") . 
                                " Vui lòng ký phụ lục gia hạn hoặc làm hợp đồng mới.";

                        $this->createOrUpdateIssue([
                            'contract_id' => $contract->id,
                            'issue_type' => 'CONTRACT_EXPIRATION',
                            'severity' => $severity,
                            'description' => $desc,
                        ]);
                        $results['contract_issues_created']++;
                    } else {
                        // Contract is safe, self-heal
                        $resolved = $this->selfHealIssue('CONTRACT_EXPIRATION', 'contract_id', $contract->id);
                        if ($resolved) $results['resolved_count']++;
                    }
                }
            }

            // ----------------------------------------------------
            // RULE 3: MISSING INVOICE / RECEIPT
            // ----------------------------------------------------
            $expenses = Transaction::where('type', 'EXPENSE')
                ->with(['documents'])
                ->get();

            foreach ($expenses as $expense) {
                $results['invoice_scanned']++;
                if ($expense->documents->isEmpty()) {
                    $txnDate = Carbon::parse($expense->transaction_date);
                    $daysOld = $now->diffInDays($txnDate);

                    $severity = 'WARNING';
                    if ($daysOld > 7) {
                        $severity = 'CRITICAL';
                    }

                    $amountFormatted = number_format($expense->amount, 0, ',', '.') . ' ₫';
                    $desc = "Khoản chi tiêu {$expense->code} trị giá {$amountFormatted} hạng mục {$expense->category} (Ngày chi: {$txnDate->format('Y-m-d')}) " . 
                            ($daysOld > 7 ? "đã quá {$daysOld} ngày" : "hiện tại") . " thiếu chứng từ hóa đơn hoặc ủy nhiệm chi đính kèm.";

                    $this->createOrUpdateIssue([
                        'transaction_id' => $expense->id,
                        'issue_type' => 'MISSING_INVOICE',
                        'severity' => $severity,
                        'description' => $desc,
                    ]);
                    $results['invoice_issues_created']++;
                } else {
                    // Document is now uploaded, self-heal!
                    $resolved = $this->selfHealIssue('MISSING_INVOICE', 'transaction_id', $expense->id);
                    if ($resolved) $results['resolved_count']++;
                }
            }

            // ----------------------------------------------------
            // RULE 4: OVERTIME LIMIT (ARTICLE 36 VIOLATION)
            // ----------------------------------------------------
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            foreach ($employees as $employee) {
                $results['ot_scanned']++;
                
                // Sum monthly overtime hours
                $timesheets = Timesheet::where('employee_id', $employee->id)
                    ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                    ->whereNotNull('check_in')
                    ->whereNotNull('check_out')
                    ->get();

                $totalOtMinutes = 0;
                foreach ($timesheets as $sheet) {
                    $checkIn = Carbon::parse($sheet->check_in);
                    $checkOut = Carbon::parse($sheet->check_out);
                    $durationHours = $checkOut->diffInMinutes($checkIn) / 60.0;
                    
                    // Standard standard_hours is typically 8.0 hours
                    $otHours = max(0, $durationHours - 8.0);
                    $totalOtMinutes += ($otHours * 60);
                }

                $totalOtHours = round($totalOtMinutes / 60.0, 1);

                if ($totalOtHours > 30) {
                    $severity = 'INFO';
                    if ($totalOtHours > 80) {
                        $severity = 'CRITICAL';
                    } elseif ($totalOtHours > 45) {
                        $severity = 'WARNING';
                    }

                    $desc = "Nhân viên {$employee->full_name} ({$employee->code}) đã làm thêm {$totalOtHours} giờ trong tháng " . 
                            "{$now->format('m/Y')}. " . 
                            ($totalOtHours > 80 ? "Vượt ngưỡng tới hạn 80 giờ của Thỏa ước 36! Cần dừng tăng ca lập tức." : 
                            ($totalOtHours > 45 ? "Vượt mốc cảnh báo tiêu chuẩn 45 giờ của Thỏa ước 36." : "Đang tiến gần mốc cảnh báo (30/45 giờ)."));

                    $this->createOrUpdateIssue([
                        'employee_id' => $employee->id,
                        'issue_type' => 'OVERTIME_LIMIT',
                        'severity' => $severity,
                        'description' => $desc,
                    ]);
                    $results['ot_issues_created']++;
                } else {
                    // OT is safe, self-heal
                    $resolved = $this->selfHealIssue('OVERTIME_LIMIT', 'employee_id', $employee->id);
                    if ($resolved) $results['resolved_count']++;
                }
            }

            $this->commitTransaction();
            return $results;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            Log::error('Error running compliance scan: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create or update compliance issue to prevent duplicates.
     */
    protected function createOrUpdateIssue(array $data)
    {
        $query = ComplianceIssue::where('issue_type', $data['issue_type'])
            ->where('status', 'OPEN');

        if (!empty($data['employee_id'])) {
            $query->where('employee_id', $data['employee_id']);
        }
        if (!empty($data['contract_id'])) {
            $query->where('contract_id', $data['contract_id']);
        }
        if (!empty($data['transaction_id'])) {
            $query->where('transaction_id', $data['transaction_id']);
        }

        $existing = $query->first();

        if ($existing) {
            // Update severity and description if changed
            if ($existing->severity !== $data['severity'] || $existing->description !== $data['description']) {
                $existing->update([
                    'severity' => $data['severity'],
                    'description' => $data['description'],
                ]);
            }
            return $existing;
        }

        $data['created_by'] = 'Compliance Engine';
        return ComplianceIssue::create($data);
    }

    /**
     * Self heal an issue once parameters are resolved.
     */
    protected function selfHealIssue(string $issueType, string $entityKey, int $entityId): bool
    {
        $existing = ComplianceIssue::where('issue_type', $issueType)
            ->where($entityKey, $entityId)
            ->where('status', 'OPEN')
            ->first();

        if ($existing) {
            $existing->update([
                'status' => 'RESOLVED',
                'resolved_at' => Carbon::now(),
                'resolved_by' => 'Compliance Engine (Tự động giải quyết)',
            ]);
            return true;
        }

        return false;
    }
}
