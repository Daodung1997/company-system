<?php

namespace App\Services\Timesheet;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Timesheet\LeaveRequestRepository;
use App\Services\AbstractService;
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
            return $request->refresh();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
