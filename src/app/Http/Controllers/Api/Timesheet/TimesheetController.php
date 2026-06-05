<?php

namespace App\Http\Controllers\Api\Timesheet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timesheet\CheckInTimesheetRequest;
use App\Http\Requests\Timesheet\CheckOutTimesheetRequest;
use App\Http\Resources\Timesheet\TimesheetResource;
use App\Services\Timesheet\TimesheetService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    public function __construct(protected TimesheetService $timesheetService) {}

    /**
     * GET /api/timesheets/monthly - Get monthly timesheets for current employee
     */
    public function monthly(Request $request)
    {
        $employeeId = auth()->user()->id;
        $yearMonth = $request->get('year_month', date('Y-m'));

        $result = $this->timesheetService->getMonthly($employeeId, $yearMonth);

        return Response::success(TimesheetResource::collection($result)->resolve());
    }

    /**
     * POST /api/timesheets/check-in - Check in for today
     */
    public function checkIn(CheckInTimesheetRequest $request)
    {
        $employeeId = auth()->user()->id;
        $timesheet = $this->timesheetService->checkIn($employeeId, $request->validated());

        return Response::success((new TimesheetResource($timesheet))->resolve());
    }

    /**
     * POST /api/timesheets/check-out - Check out for today
     */
    public function checkOut(CheckOutTimesheetRequest $request)
    {
        $employeeId = auth()->user()->id;
        $timesheet = $this->timesheetService->checkOut($employeeId, $request->validated());

        return Response::success((new TimesheetResource($timesheet))->resolve());
    }

    /**
     * GET /api/timesheets/manage - Get timesheets for admin/manager with filters
     */
    public function manage(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('view-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $filters = $request->only(['q', 'status', 'start_date', 'end_date', 'per_page']);
        $result = $this->timesheetService->getForAdmin($filters);

        return Response::success([
            'data' => TimesheetResource::collection($result->items())->resolve(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ]
        ]);
    }

    public function statistics(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('view-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $yearMonth = $request->get('year_month', date('Y-m'));
        $page = (int)$request->get('page', 1);
        $perPage = (int)$request->get('per_page', 15);
        $search = $request->get('search');
        $result = $this->timesheetService->getStatistics($yearMonth, $page, $perPage, $search);

        return Response::success([
            'data' => $result->items(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ]
        ]);
    }

    /**
     * POST /api/timesheets/store-manual - Admin manually records or corrects a timesheet
     */
    public function storeManual(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('approve-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:Y-m-d H:i:s',
            'check_out' => 'nullable|date_format:Y-m-d H:i:s',
            'status' => 'nullable|in:PRESENT,LATE,ABSENT',
            'note' => 'nullable|string',
        ]);

        $timesheet = $this->timesheetService->storeManual($validated);

        return Response::success((new TimesheetResource($timesheet))->resolve());
    }

    /**
     * GET /api/timesheets/working-hour-configs - Get list of working hour configs
     */
    public function listWorkingHourConfigs()
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('view-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $result = $this->timesheetService->listWorkingHourConfigs();
        return Response::success($result->toArray());
    }

    /**
     * POST /api/timesheets/working-hour-configs - Create or update working hour config
     */
    public function storeWorkingHourConfig(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('approve-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:working_hour_configs,id',
            'name' => 'required|string|max:255',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'is_default' => 'nullable|boolean',
            'allow_overtime' => 'nullable|boolean',
            'max_overtime_hours' => 'nullable|numeric|min:0',
        ]);

        $result = $this->timesheetService->storeWorkingHourConfig($validated);
        return Response::success($result->toArray());
    }

    /**
     * DELETE /api/timesheets/working-hour-configs/{id} - Delete working hour config
     */
    public function deleteWorkingHourConfig($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('approve-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $this->timesheetService->deleteWorkingHourConfig((int)$id);
        return Response::success(['message' => 'Đã xóa cấu hình thành công.']);
    }

    /**
     * GET /api/timesheets/employee-shifts - List assigned employee shifts
     */
    public function listEmployeeShifts(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('view-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $filters = $request->only(['date', 'year_month', 'employee_id']);
        $result = $this->timesheetService->listEmployeeShifts($filters);
        return Response::success($result->toArray());
    }

    /**
     * POST /api/timesheets/employee-shifts - Assign shift to employees
     */
    public function storeEmployeeShift(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('approve-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'required|integer|exists:employees,id',
            'dates' => 'required|array',
            'dates.*' => 'required|date',
            'working_hour_config_id' => 'required|integer|exists:working_hour_configs,id',
        ]);

        $result = $this->timesheetService->storeEmployeeShift($validated);
        return Response::success(['message' => 'Đã phân ca cho nhân viên thành công.']);
    }

    /**
     * DELETE /api/timesheets/employee-shifts/{id} - Remove employee shift assignment
     */
    public function deleteEmployeeShift($id)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('approve-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $this->timesheetService->deleteEmployeeShift((int)$id);
        return Response::success(['message' => 'Đã xóa phân ca thành công.']);
    }

    /**
     * GET /api/timesheets/employee-shifts/calendar - Get employee monthly shifts & leaves calendar grid
     */
    public function listEmployeeShiftsCalendar(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('view-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $params = [
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 15),
            'search' => $request->get('search'),
            'department_id' => $request->get('department_id'),
            'job_title_id' => $request->get('job_title_id'),
            'year_month' => $request->get('year_month'),
        ];

        $result = $this->timesheetService->listEmployeeShiftsCalendar($params);

        return Response::pagination(
            \App\Http\Resources\Employee\EmployeeListResource::collection($result),
            $result->total(),
            $result->currentPage(),
            $result->perPage()
        );
    }

    /**
     * POST /api/timesheets/employee-shifts/reset - Reset custom shifts to system default
     */
    public function resetEmployeeShifts(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('approve-timesheets')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'required|integer|exists:employees,id',
            'dates' => 'required|array',
            'dates.*' => 'required|date',
        ]);

        $this->timesheetService->resetEmployeeShifts($validated);
        return Response::success(['message' => 'Đã reset ca làm việc về mặc định thành công.']);
    }

    /**
     * GET /api/timesheets/payroll - Get monthly payroll list
     */
    public function getPayroll(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('view-payslips')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $yearMonth = $request->get('year_month', date('Y-m'));
        $page = (int)$request->get('page', 1);
        $perPage = (int)$request->get('per_page', 15);
        $search = $request->get('search');
        
        $result = $this->timesheetService->getPayroll($yearMonth, $page, $perPage, $search);
        return Response::success($result);
    }

    /**
     * POST /api/timesheets/payroll - Save/Update a payroll record
     */
    public function savePayroll(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('create-payslips')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'year_month' => 'required|string|max:7',
            'base_salary' => 'required|numeric',
            'standard_working_days' => 'required|numeric',
            'actual_working_days' => 'required|numeric',
            'overtime_hours' => 'required|numeric',
            'overtime_salary' => 'required|numeric',
            'overtime_hours_normal' => 'nullable|numeric',
            'overtime_salary_normal' => 'nullable|numeric',
            'overtime_hours_weekend' => 'nullable|numeric',
            'overtime_salary_weekend' => 'nullable|numeric',
            'overtime_hours_holiday' => 'nullable|numeric',
            'overtime_salary_holiday' => 'nullable|numeric',
            'allowance_attendance' => 'required|numeric',
            'deduction_late' => 'required|numeric',
            'deduction_leave' => 'required|numeric',
            'deduction_union' => 'nullable|numeric',
            'deduction_tax' => 'nullable|numeric',
            'advance_payment' => 'required|numeric',
            'net_salary' => 'required|numeric',
            'status' => 'required|string|in:PENDING,PAID',
            'note' => 'nullable|string|max:500',
        ]);

        $payslip = $this->timesheetService->savePayroll($validated);
        return Response::success($payslip->toArray());
    }

    /**
     * GET /api/timesheets/payroll/export-excel - Export payroll to Excel (HTML table representation)
     */
    public function exportPayrollExcel(Request $request)
    {
        $user = auth('api')->user();
        if (!$user->hasPermissionTo('view-payslips')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $yearMonth = $request->query('year_month', now()->format('Y-m'));
        $search = $request->query('search');

        $payrollResponse = $this->timesheetService->getPayroll($yearMonth, 1, 100000, $search);
        $data = $payrollResponse['data'] ?? [];

        // Build HTML table for Excel
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" /></head>';
        $html .= '<body>';
        $html .= '<h2>BẢNG LƯƠNG NHÂN VIÊN THÁNG ' . htmlspecialchars($yearMonth) . '</h2>';
        $html .= '<table border="1">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #1e3a8a; color: #ffffff; font-weight: bold;">';
        $html .= '<th>STT</th>';
        $html .= '<th>Mã nhân viên</th>';
        $html .= '<th>Họ và tên</th>';
        $html .= '<th>Email</th>';
        $html .= '<th>Lương cơ bản</th>';
        $html .= '<th>Công tiêu chuẩn</th>';
        $html .= '<th>Công thực tế</th>';
        $html .= '<th>Tổng giờ OT</th>';
        $html .= '<th>OT thường (h)</th>';
        $html .= '<th>Lương OT thường</th>';
        $html .= '<th>OT cuối tuần (h)</th>';
        $html .= '<th>Lương OT cuối tuần</th>';
        $html .= '<th>OT ngày lễ (h)</th>';
        $html .= '<th>Lương OT ngày lễ</th>';
        $html .= '<th>Tổng tiền OT</th>';
        $html .= '<th>Chuyên cần</th>';
        $html .= '<th>Trừ đi muộn</th>';
        $html .= '<th>Trừ nghỉ phép</th>';
        $html .= '<th>Phí công đoàn</th>';
        $html .= '<th>Thuế TNCN</th>';
        $html .= '<th>Đã trả trước</th>';
        $html .= '<th>Thực lĩnh</th>';
        $html .= '<th>Trạng thái</th>';
        $html .= '<th>Ghi chú</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($data as $index => $item) {
            $html .= '<tr>';
            $html .= '<td>' . ($index + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['employee_code'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($item['full_name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($item['email'] ?? '') . '</td>';
            $html .= '<td>' . ($item['base_salary'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['standard_working_days'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['actual_working_days'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['overtime_hours'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['overtime_hours_normal'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['overtime_salary_normal'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['overtime_hours_weekend'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['overtime_salary_weekend'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['overtime_hours_holiday'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['overtime_salary_holiday'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['overtime_salary'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['allowance_attendance'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['deduction_late'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['deduction_leave'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['deduction_union'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['deduction_tax'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['advance_payment'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['net_salary'] ?? 0) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($item['note'] ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</body>';
        $html .= '</html>';

        return response($html, 200)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="Bang_luong_' . $yearMonth . '.xls"');
    }

    /**
     * GET /api/timesheets/payroll/export-pdf - Export payroll to PDF (Landscape layout)
     */
    public function exportPayrollPdf(Request $request)
    {
        $user = auth('api')->user();
        if (!$user->hasPermissionTo('view-payslips')) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $yearMonth = $request->query('year_month', now()->format('Y-m'));
        $search = $request->query('search');

        $payrollResponse = $this->timesheetService->getPayroll($yearMonth, 1, 100000, $search);
        $data = $payrollResponse['data'] ?? [];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payroll', compact('data', 'yearMonth'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("Bang_luong_{$yearMonth}.pdf");
    }
}
