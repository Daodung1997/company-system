<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Contract;
use App\Models\Transaction;
use App\Models\ComplianceIssue;
use App\Supports\Facades\Response\Response;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard - Aggregated statistics for the company dashboard
     */
    public function index(Request $request)
    {
        $employee = auth('api')->user();
        if (!$employee) {
            return Response::success([]);
        }

        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Allow filtering by year (default: current year)
        $year = $request->get('year', $currentYear);

        // ====================================================================
        // 1. KPI CARDS - Quick stat overview
        // ====================================================================
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'ACTIVE')->count();

        $totalContracts = Contract::count();
        $activeContracts = Contract::where('status', 'ACTIVE')->count();
        $expiringContracts = Contract::where('status', 'ACTIVE')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $now->copy()->addDays(30)->toDateString())
            ->count();

        $totalRevenue = Transaction::where('type', 'REVENUE')
            ->whereYear('transaction_date', $year)
            ->sum('amount');

        $totalExpense = Transaction::where('type', 'EXPENSE')
            ->whereYear('transaction_date', $year)
            ->sum('amount');

        $netIncome = $totalRevenue - $totalExpense;

        $pendingLeaves = DB::table('leave_requests')
            ->join('employees', 'leave_requests.employee_id', '=', 'employees.id')
            ->where('leave_requests.status', 'PENDING')
            ->count();

        $openComplianceIssues = ComplianceIssue::where('status', 'OPEN')
            ->count();

        $criticalIssues = ComplianceIssue::where('status', 'OPEN')
            ->where('severity', 'CRITICAL')
            ->count();

        // Compliance score calculation
        $warningIssues = ComplianceIssue::where('status', 'OPEN')
            ->where('severity', 'WARNING')
            ->count();
        $infoIssues = ComplianceIssue::where('status', 'OPEN')
            ->where('severity', 'INFO')
            ->count();
        $complianceScore = max(0, 100 - ($criticalIssues * 15) - ($warningIssues * 5) - ($infoIssues * 1));

        // ====================================================================
        // 2. MONTHLY REVENUE/EXPENSE TREND (12 months of selected year)
        // ====================================================================
        $monthlyTrend = [];
        for ($m = 1; $m <= 12; $m++) {
            $rev = Transaction::where('type', 'REVENUE')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $m)
                ->sum('amount');

            $exp = Transaction::where('type', 'EXPENSE')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $m)
                ->sum('amount');

            $monthlyTrend[] = [
                'month' => $m,
                'label' => "T" . $m,
                'revenue' => round((float)$rev, 0),
                'expense' => round((float)$exp, 0),
                'profit' => round((float)$rev - (float)$exp, 0),
            ];
        }

        // ====================================================================
        // 3. CONTRACT DISTRIBUTION (by type)
        // ====================================================================
        $contractsByType = Contract::select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->map(fn($item) => [
                'label' => $this->getContractTypeLabel($item->type),
                'value' => $item->count,
                'type' => $item->type,
            ])
            ->toArray();

        // ====================================================================
        // 4. EXPENSE BY CATEGORY (Top categories)
        // ====================================================================
        $expenseByCategory = Transaction::where('type', 'EXPENSE')
            ->whereYear('transaction_date', $year)
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn($item) => [
                'label' => $item->category ?: 'Khác',
                'value' => round((float)$item->total, 0),
                'count' => $item->count,
            ])
            ->toArray();

        // ====================================================================
        // 5. EMPLOYEE ATTENDANCE SUMMARY (current month)
        // ====================================================================
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $attendanceSummary = DB::table('timesheets')
            ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
            ->whereBetween('timesheets.date', [$startOfMonth, $endOfMonth])
            ->select(
                'employees.id',
                'employees.full_name',
                'employees.code',
                DB::raw('COUNT(CASE WHEN timesheets.check_in IS NOT NULL THEN 1 END) as days_present'),
                DB::raw('COUNT(CASE WHEN timesheets.check_in IS NULL THEN 1 END) as days_absent'),
                DB::raw('ROUND(SUM(CASE WHEN timesheets.check_in IS NOT NULL AND timesheets.check_out IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, timesheets.check_in, timesheets.check_out) / 60.0 ELSE 0 END), 1) as total_hours'),
                DB::raw('ROUND(SUM(CASE WHEN timesheets.check_in IS NOT NULL AND timesheets.check_out IS NOT NULL AND TIMESTAMPDIFF(MINUTE, timesheets.check_in, timesheets.check_out) / 60.0 > 8 THEN TIMESTAMPDIFF(MINUTE, timesheets.check_in, timesheets.check_out) / 60.0 - 8 ELSE 0 END), 1) as overtime_hours')
            )
            ->groupBy('employees.id', 'employees.full_name', 'employees.code')
            ->get()
            ->toArray();

        // ====================================================================
        // 6. COMPLIANCE ISSUES BREAKDOWN
        // ====================================================================
        $complianceByType = ComplianceIssue::where('status', 'OPEN')
            ->select('issue_type', 'severity', DB::raw('COUNT(*) as count'))
            ->groupBy('issue_type', 'severity')
            ->get()
            ->map(fn($item) => [
                'type' => $item->issue_type,
                'label' => $this->getIssueTypeLabel($item->issue_type),
                'severity' => $item->severity,
                'count' => $item->count,
            ])
            ->toArray();

        // ====================================================================
        // 7. RECENT TRANSACTIONS (latest 5)
        // ====================================================================
        $recentTransactions = Transaction::orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'type' => $item->type,
                'category' => $item->category,
                'amount' => round((float)$item->amount, 0),
                'transaction_date' => $item->transaction_date,
                'description' => $item->description,
            ])
            ->toArray();

        // ====================================================================
        // 8. DEPARTMENT EMPLOYEE DISTRIBUTION
        // ====================================================================
        $departmentDistribution = DB::table('employees')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->select('departments.name as label', DB::raw('COUNT(*) as value'))
            ->groupBy('departments.name')
            ->get()
            ->toArray();

        // ====================================================================
        // ASSEMBLE RESPONSE
        // ====================================================================
        return Response::success([
            'year' => (int) $year,
            'kpi' => [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'total_contracts' => $totalContracts,
                'active_contracts' => $activeContracts,
                'expiring_contracts' => $expiringContracts,
                'total_revenue' => round((float)$totalRevenue, 0),
                'total_expense' => round((float)$totalExpense, 0),
                'net_income' => round((float)$netIncome, 0),
                'pending_leaves' => $pendingLeaves,
                'open_compliance_issues' => $openComplianceIssues,
                'critical_issues' => $criticalIssues,
                'compliance_score' => $complianceScore,
            ],
            'monthly_trend' => $monthlyTrend,
            'contracts_by_type' => $contractsByType,
            'expense_by_category' => $expenseByCategory,
            'attendance_summary' => $attendanceSummary,
            'compliance_by_type' => $complianceByType,
            'recent_transactions' => $recentTransactions,
            'department_distribution' => $departmentDistribution,
        ]);
    }

    private function getContractTypeLabel(string $type): string
    {
        return match ($type) {
            'LABOR' => 'Hợp đồng Lao động',
            'VENDOR' => 'HĐ Thầu phụ/Đối tác',
            'CLIENT' => 'HĐ Dịch vụ Khách hàng',
            'PROBATION' => 'HĐ Thử việc',
            default => $type,
        };
    }

    private function getIssueTypeLabel(string $type): string
    {
        return match ($type) {
            'VISA_EXPIRATION' => 'Hạn thẻ cư trú',
            'CONTRACT_EXPIRATION' => 'Hết hạn hợp đồng',
            'MISSING_INVOICE' => 'Thiếu chứng từ',
            'OVERTIME_LIMIT' => 'Tăng ca Thỏa ước 36',
            default => $type,
        };
    }
}
