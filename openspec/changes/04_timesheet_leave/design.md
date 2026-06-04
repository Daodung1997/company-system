# TECHNICAL DESIGN: HỆ THỐNG CHẤM CÔNG VÀ XÉT DUYỆT NGHỈ PHÉP

## 1. Thiết kế Cơ sở dữ liệu

### 1.1. Bảng `timesheets`
*   Lưu lịch sử chấm công hàng ngày của nhân viên.
*   Khóa ngoại `employee_id` liên kết với bảng `employees`.

### 1.2. Bảng `leave_requests`
*   Quản lý thông tin đơn xin nghỉ phép.
*   Khóa ngoại `employee_id` (người xin nghỉ) và `approved_by` (người duyệt).

---

## 2. Bản đồ File triển khai

```text
src/
├── app/
│   ├── Models/
│   │   ├── Timesheet.php
│   │   └── LeaveRequest.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── Timesheet/
│   │   │           ├── TimesheetController.php
│   │   │           └── LeaveRequestController.php
│   │   ├── Requests/
│   │   │   └── Timesheet/
│   │   │       ├── CheckInRequest.php
│   │   │       └── UpdateLeaveRequestStatusRequest.php
│   │   └── Resources/
│   │       └── Timesheet/
│   │           ├── TimesheetResource.php
│   │           └── LeaveRequestResource.php
│   ├── Services/
│   │   └── Api/
│   │       └── Timesheet/
│   │           ├── TimesheetService.php
│   │           └── LeaveRequestService.php
└── routes/
    └── api/
        └── timesheet.php
```
