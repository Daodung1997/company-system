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
            'employee_issues_created' => 0,
            'expense_issues_created' => 0,
            'revenue_issues_created' => 0,
        ];

        $this->beginTransaction();
        try {
            $now = Carbon::now();

            // ----------------------------------------------------
            // RULE 1: VISA EXPIRATION & EMPLOYEE COMPLIANCE
            // ----------------------------------------------------
            $employees = Employee::all();
            foreach ($employees as $employee) {
                $results['visa_scanned']++;
                
                // A. Visa Expiration
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
                        $resolved = $this->selfHealIssue('VISA_EXPIRATION', 'employee_id', $employee->id);
                        if ($resolved) $results['resolved_count']++;
                    }
                }

                // B. Thiếu hợp đồng lao động (Missing active LABOR contract)
                $hasLaborContract = Contract::where('employee_id', $employee->id)
                    ->where('type', 'LABOR')
                    ->where('status', 'ACTIVE')
                    ->exists();

                if (!$hasLaborContract) {
                    $this->createOrUpdateIssue([
                        'employee_id' => $employee->id,
                        'issue_type' => 'MISSING_LABOR_CONTRACT',
                        'severity' => 'CRITICAL',
                        'description' => "Nhân viên {$employee->full_name} ({$employee->code}) chưa có Hợp đồng lao động hoạt động (ACTIVE) trên hệ thống.",
                    ]);
                    $results['employee_issues_created']++;
                } else {
                    $resolved = $this->selfHealIssue('MISSING_LABOR_CONTRACT', 'employee_id', $employee->id);
                    if ($resolved) $results['resolved_count']++;
                }

                // C. Thiếu CCCD (Missing CCCD / Identity)
                $hasCccd = !empty($employee->identity_number);
                if (!$hasCccd) {
                    $this->createOrUpdateIssue([
                        'employee_id' => $employee->id,
                        'issue_type' => 'MISSING_CCCD',
                        'severity' => 'WARNING',
                        'description' => "Nhân viên {$employee->full_name} ({$employee->code}) thiếu thông tin số CCCD/Hộ chiếu trong hồ sơ.",
                    ]);
                    $results['employee_issues_created']++;
                } else {
                    $resolved = $this->selfHealIssue('MISSING_CCCD', 'employee_id', $employee->id);
                    if ($resolved) $results['resolved_count']++;
                }

                // D. Thiếu mã số thuế (Missing Tax Code)
                $hasTaxCode = !empty($employee->tax_code);
                if (!$hasTaxCode) {
                    $this->createOrUpdateIssue([
                        'employee_id' => $employee->id,
                        'issue_type' => 'MISSING_TAX_CODE',
                        'severity' => 'WARNING',
                        'description' => "Nhân viên {$employee->full_name} ({$employee->code}) thiếu thông tin Mã số thuế cá nhân.",
                    ]);
                    $results['employee_issues_created']++;
                } else {
                    $resolved = $this->selfHealIssue('MISSING_TAX_CODE', 'employee_id', $employee->id);
                    if ($resolved) $results['resolved_count']++;
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
                        $resolved = $this->selfHealIssue('CONTRACT_EXPIRATION', 'contract_id', $contract->id);
                        if ($resolved) $results['resolved_count']++;
                    }
                }
            }

            // ----------------------------------------------------
            // RULE 3: EXPENSE COMPLIANCE (CHI PHÍ)
            // ----------------------------------------------------
            $expenses = Transaction::where('type', 'EXPENSE')
                ->with(['documents'])
                ->get();

            foreach ($expenses as $expense) {
                $results['invoice_scanned']++;
                $amountFormatted = number_format($expense->amount, 0, ',', '.') . ' ₫';

                // Check 1: Thiếu invoice
                $hasInvoice = false;
                foreach ($expense->documents as $doc) {
                    if (\Illuminate\Support\Str::contains(strtolower($doc->origin_name), ['invoice', 'hoá đơn', 'hoa don', 'bill', 'receipt'])) {
                        $hasInvoice = true;
                        break;
                    }
                }

                if (!$hasInvoice) {
                    $this->createOrUpdateIssue([
                        'transaction_id' => $expense->id,
                        'issue_type' => 'MISSING_INVOICE',
                        'severity' => 'CRITICAL',
                        'description' => "Khoản chi tiêu {$expense->code} ({$amountFormatted}) hạng mục {$expense->category} thiếu hóa đơn (Invoice/Hoá đơn) đính kèm.",
                    ]);
                    $results['expense_issues_created']++;
                } else {
                    $resolved = $this->selfHealIssue('MISSING_INVOICE', 'transaction_id', $expense->id);
                    if ($resolved) $results['resolved_count']++;
                }

                // Check 2: Thiếu hợp đồng
                $hasContract = false;
                foreach ($expense->documents as $doc) {
                    if ($doc->contract_id !== null || \Illuminate\Support\Str::contains(strtolower($doc->origin_name), ['contract', 'hợp đồng', 'hop dong', 'agreement'])) {
                        $hasContract = true;
                        break;
                    }
                }

                if (!$hasContract) {
                    $this->createOrUpdateIssue([
                        'transaction_id' => $expense->id,
                        'issue_type' => 'MISSING_CONTRACT',
                        'severity' => 'WARNING',
                        'description' => "Khoản chi tiêu {$expense->code} ({$amountFormatted}) hạng mục {$expense->category} thiếu Hợp đồng liên kết.",
                    ]);
                    $results['expense_issues_created']++;
                } else {
                    $resolved = $this->selfHealIssue('MISSING_CONTRACT', 'transaction_id', $expense->id);
                    if ($resolved) $results['resolved_count']++;
                }

                // Check 3: Thiếu chứng từ thanh toán
                $hasPaymentVoucher = false;
                foreach ($expense->documents as $doc) {
                    if (\Illuminate\Support\Str::contains(strtolower($doc->origin_name), ['payment', 'thanh toán', 'thanh toan', 'ủy nhiệm chi', 'uy nhiem chi', 'phiếu chi', 'phieu chi', 'voucher'])) {
                        $hasPaymentVoucher = true;
                        break;
                    }
                }

                if (!$hasPaymentVoucher) {
                    $this->createOrUpdateIssue([
                        'transaction_id' => $expense->id,
                        'issue_type' => 'MISSING_PAYMENT_VOUCHER',
                        'severity' => 'WARNING',
                        'description' => "Khoản chi tiêu {$expense->code} ({$amountFormatted}) hạng mục {$expense->category} thiếu Chứng từ thanh toán / Ủy nhiệm chi.",
                    ]);
                    $results['expense_issues_created']++;
                } else {
                    $resolved = $this->selfHealIssue('MISSING_PAYMENT_VOUCHER', 'transaction_id', $expense->id);
                    if ($resolved) $results['resolved_count']++;
                }
            }

            // ----------------------------------------------------
            // RULE 4: REVENUE COMPLIANCE (DOANH THU)
            // ----------------------------------------------------
            $revenues = Transaction::where('type', 'REVENUE')
                ->with(['documents'])
                ->get();

            foreach ($revenues as $revenue) {
                $amountFormatted = number_format($revenue->amount, 0, ',', '.') . ' ₫';

                // Check 1: Thiếu PO (Purchase Order)
                $hasPO = false;
                foreach ($revenue->documents as $doc) {
                    if (\Illuminate\Support\Str::contains(strtolower($doc->origin_name), ['po', 'purchase order', 'đơn đặt hàng', 'don dat hang', 'order'])) {
                        $hasPO = true;
                        break;
                    }
                }

                if (!$hasPO) {
                    $this->createOrUpdateIssue([
                        'transaction_id' => $revenue->id,
                        'issue_type' => 'MISSING_PO',
                        'severity' => 'CRITICAL',
                        'description' => "Khoản doanh thu {$revenue->code} ({$amountFormatted}) hạng mục {$revenue->category} thiếu Đơn đặt hàng (PO) đi kèm.",
                    ]);
                    $results['revenue_issues_created']++;
                } else {
                    $resolved = $this->selfHealIssue('MISSING_PO', 'transaction_id', $revenue->id);
                    if ($resolved) $results['resolved_count']++;
                }

                // Check 2: Thiếu nghiệm thu
                $hasAcceptance = false;
                foreach ($revenue->documents as $doc) {
                    if (\Illuminate\Support\Str::contains(strtolower($doc->origin_name), ['nghiệm thu', 'nghiem thu', 'acceptance', 'biên bản', 'bien ban'])) {
                        $hasAcceptance = true;
                        break;
                    }
                }

                if (!$hasAcceptance) {
                    $this->createOrUpdateIssue([
                        'transaction_id' => $revenue->id,
                        'issue_type' => 'MISSING_ACCEPTANCE',
                        'severity' => 'CRITICAL',
                        'description' => "Khoản doanh thu {$revenue->code} ({$amountFormatted}) hạng mục {$revenue->category} thiếu Biên bản nghiệm thu bàn giao.",
                    ]);
                    $results['revenue_issues_created']++;
                } else {
                    $resolved = $this->selfHealIssue('MISSING_ACCEPTANCE', 'transaction_id', $revenue->id);
                    if ($resolved) $results['resolved_count']++;
                }

                // Check 3: Thiếu hợp đồng
                $hasContract = false;
                foreach ($revenue->documents as $doc) {
                    if ($doc->contract_id !== null || \Illuminate\Support\Str::contains(strtolower($doc->origin_name), ['contract', 'hợp đồng', 'hop dong', 'agreement'])) {
                        $hasContract = true;
                        break;
                    }
                }

                if (!$hasContract) {
                    $this->createOrUpdateIssue([
                        'transaction_id' => $revenue->id,
                        'issue_type' => 'MISSING_CONTRACT',
                        'severity' => 'WARNING',
                        'description' => "Khoản doanh thu {$revenue->code} ({$amountFormatted}) hạng mục {$revenue->category} thiếu Hợp đồng liên kết.",
                    ]);
                    $results['revenue_issues_created']++;
                } else {
                    $resolved = $this->selfHealIssue('MISSING_CONTRACT', 'transaction_id', $revenue->id);
                    if ($resolved) $results['resolved_count']++;
                }
            }

            // ----------------------------------------------------
            // RULE 5: OVERTIME LIMIT (ARTICLE 36 VIOLATION)
            // ----------------------------------------------------
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            foreach ($employees as $employee) {
                $results['ot_scanned']++;
                
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
                    
                    $otHours = max(0, $durationHours - 8.0);
                    $totalOtMinutes += ($otHours * 60);
                }

                $totalOtHours = round($totalOtMinutes / 60.0, 1);

                if ($totalOtHours > 20) {
                    $severity = 'INFO';
                    if ($totalOtHours > 40) {
                        $severity = 'CRITICAL';
                    } elseif ($totalOtHours > 30) {
                        $severity = 'WARNING';
                    }

                    $desc = "Nhân viên {$employee->full_name} ({$employee->code}) đã làm thêm {$totalOtHours} giờ trong tháng " . 
                            "{$now->format('m/Y')}. " . 
                            ($totalOtHours > 40 ? "Vượt ngưỡng giới hạn 40 giờ/tháng của Luật Lao động Việt Nam! Cần dừng tăng ca lập tức." : 
                            ($totalOtHours > 30 ? "Vượt mốc cảnh báo 30 giờ/tháng (giới hạn tối đa là 40 giờ)." : "Đang tiến gần mốc cảnh báo (20/30 giờ)."));

                    $this->createOrUpdateIssue([
                        'employee_id' => $employee->id,
                        'issue_type' => 'OVERTIME_LIMIT',
                        'severity' => $severity,
                        'description' => $desc,
                    ]);
                    $results['ot_issues_created']++;
                } else {
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
