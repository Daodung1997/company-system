<?php

namespace App\Services\Timesheet;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Timesheet\LeaveRequestRepository;
use App\Services\AbstractService;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;

class LeaveRequestService extends AbstractService
{
    public function __construct(
        protected LeaveRequestRepository $leaveRequestRepository
    ) {}

    /**
     * Get all leave requests for an employee.
     */
    public function getForEmployee(int $employeeId)
    {
        return $this->leaveRequestRepository->getByEmployeeId($employeeId);
    }

    /**
     * Get all leave requests across the company (for managers).
     */
    public function listAllPending()
    {
        return $this->leaveRequestRepository->getInstance()
            ->with(['employee', 'approver'])
            ->orderByRaw("CASE status WHEN 'PENDING' THEN 1 WHEN 'APPROVED' THEN 2 WHEN 'REJECTED' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new leave request.
     */
    public function create(int $employeeId, array $data)
    {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];

        if (Carbon::parse($startDate)->greaterThan(Carbon::parse($endDate))) {
            throw new BusinessException(
                ExceptionCode::LEAVE_REQUEST_INVALID_DATES,
                'Ngày bắt đầu không được lớn hơn ngày kết thúc.',
                400
            );
        }

        // Check for overlapping requests
        $overlap = $this->leaveRequestRepository->hasOverlappingLeave($employeeId, $startDate, $endDate);
        if ($overlap) {
            throw new BusinessException(
                ExceptionCode::LEAVE_REQUEST_OVERLAP,
                'Khoảng thời gian nghỉ trùng lặp với đơn xin nghỉ khác.',
                400
            );
        }

        $attachmentPath = null;
        if (isset($data['attachment']) && $data['attachment'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['attachment'];
            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $attachmentPath = $file->storeAs('leave_attachments', $filename, 'public');
        }

        $this->beginTransaction();
        try {
            $leaveRequest = $this->leaveRequestRepository->create([
                'employee_id' => $employeeId,
                'leave_type' => $data['leave_type'],
                'leave_session' => $data['leave_session'] ?? 'ALL',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $data['reason'] ?? null,
                'attachment_path' => $attachmentPath,
                'status' => 'PENDING',
            ]);

            $this->commitTransaction();

            // Send notification to all managers about the new leave request
            $employeeName = $leaveRequest->employee?->full_name ?? 'Nhân viên';
            NotificationService::sendToRole(
                'MANAGER',
                'LEAVE_REQUEST_CREATED',
                'Đơn xin nghỉ phép mới',
                "{$employeeName} đã gửi đơn xin nghỉ phép từ {$startDate} đến {$endDate}.",
                '/leave-request/pending',
                ['leave_request_id' => $leaveRequest->id]
            );
            // Also notify ADMINs
            NotificationService::sendToRole(
                'ADMIN',
                'LEAVE_REQUEST_CREATED',
                'Đơn xin nghỉ phép mới',
                "{$employeeName} đã gửi đơn xin nghỉ phép từ {$startDate} đến {$endDate}.",
                '/leave-request/pending',
                ['leave_request_id' => $leaveRequest->id]
            );

            return $leaveRequest;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Approve or reject a leave request.
     */
    public function approve(int $requestId, int $managerId, array $data)
    {
        $request = $this->leaveRequestRepository->find($requestId);
        if (!$request) {
            throw new BusinessException(
                ExceptionCode::LEAVE_REQUEST_NOT_FOUND,
                'Đơn xin nghỉ không tồn tại.',
                404
            );
        }

        if ($request->status !== 'PENDING') {
            throw new BusinessException(
                ExceptionCode::LEAVE_REQUEST_CANNOT_APPROVE,
                'Đơn xin nghỉ này đã được xử lý rồi.',
                400
            );
        }

        $this->beginTransaction();
        try {
            $this->leaveRequestRepository->update($request->id, [
                'status' => $data['status'], // APPROVED or REJECTED
                'approved_by' => $managerId,
                'approved_at' => Carbon::now()->toDateTimeString(),
                'approver_note' => $data['approver_note'] ?? null,
            ]);

            $this->commitTransaction();

            // Send notification back to the employee who submitted the leave request
            $request->refresh();
            $statusLabel = $data['status'] === 'APPROVED' ? 'được phê duyệt' : 'bị từ chối';
            $managerName = $request->approver?->full_name ?? 'Quản lý';
            NotificationService::send(
                $request->employee_id,
                'LEAVE_REQUEST_' . $data['status'],
                "Đơn xin nghỉ phép đã {$statusLabel}",
                "Đơn xin nghỉ phép của bạn từ {$request->start_date->format('Y-m-d')} đến {$request->end_date->format('Y-m-d')} đã {$statusLabel} bởi {$managerName}." . ($data['approver_note'] ? " Ghi chú: {$data['approver_note']}" : ''),
                '/leave-request',
                ['leave_request_id' => $request->id]
            );

            return $request;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
