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
        if ($user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::MANAGER && $user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::ADMIN) {
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

    /**
     * GET /api/timesheets/statistics - Get monthly attendance statistics for all employees
     */
    public function statistics(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::MANAGER && $user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::ADMIN) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $yearMonth = $request->get('year_month', date('Y-m'));
        $result = $this->timesheetService->getStatistics($yearMonth);

        return Response::success($result->toArray());
    }

    /**
     * POST /api/timesheets/store-manual - Admin manually records or corrects a timesheet
     */
    public function storeManual(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::MANAGER && $user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::ADMIN) {
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
        if ($user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::MANAGER && $user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::ADMIN) {
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
        if ($user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::MANAGER && $user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::ADMIN) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:working_hour_configs,id',
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'is_default' => 'nullable|boolean',
            'saturday_mode' => 'nullable|integer|in:0,1,2',
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
        if ($user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::MANAGER && $user->role !== \App\Constants\Master\Models\Employee\EmployeeRoleConst::ADMIN) {
            throw new \App\Exceptions\BusinessException(
                \App\Constants\Commons\ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $this->timesheetService->deleteWorkingHourConfig((int)$id);
        return Response::success(['message' => 'Đã xóa cấu hình thành công.']);
    }
}
