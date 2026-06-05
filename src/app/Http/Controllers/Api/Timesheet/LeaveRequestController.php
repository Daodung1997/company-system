<?php

namespace App\Http\Controllers\Api\Timesheet;

use App\Constants\Master\Models\Employee\EmployeeRoleConst;
use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Timesheet\ApproveLeaveRequest;
use App\Http\Requests\Timesheet\StoreLeaveRequest;
use App\Http\Resources\Timesheet\LeaveRequestResource;
use App\Services\Timesheet\LeaveRequestService;
use App\Supports\Facades\Response\Response;

class LeaveRequestController extends Controller
{
    public function __construct(protected LeaveRequestService $leaveRequestService) {}

    /**
     * GET /api/leave-requests - List all leave requests for logged-in employee
     */
    public function index()
    {
        $employeeId = auth()->user()->id;
        $result = $this->leaveRequestService->getForEmployee($employeeId);

        return Response::success(LeaveRequestResource::collection($result)->resolve());
    }

    /**
     * POST /api/leave-requests - Create a new leave request
     */
    public function store(StoreLeaveRequest $request)
    {
        $employeeId = auth()->user()->id;
        $leaveRequest = $this->leaveRequestService->create($employeeId, $request->validated());

        return Response::success((new LeaveRequestResource($leaveRequest))->resolve());
    }

    /**
     * GET /api/leave-requests/pending - List all pending leave requests for company (Managers/Admins only)
     */
    public function listPending()
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('view-leave-requests')) {
            throw new BusinessException(
                ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $result = $this->leaveRequestService->listAllPending();

        return Response::success(LeaveRequestResource::collection($result)->resolve());
    }

    /**
     * POST /api/leave-requests/{id}/approve - Approve or reject a leave request (Managers/Admins only)
     */
    public function approve(int $id, ApproveLeaveRequest $request)
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('approve-leave-requests')) {
            throw new BusinessException(
                ExceptionCode::EMPLOYEE_PERMISSION_DENIED,
                'Bạn không có quyền thực hiện hành động này.',
                403
            );
        }

        $leaveRequest = $this->leaveRequestService->approve($id, $user->id, $request->validated());

        return Response::success((new LeaveRequestResource($leaveRequest))->resolve());
    }
}
