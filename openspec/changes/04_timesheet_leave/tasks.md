# TASKS CHECKLIST: HỆ THỐNG CHẤM CÔNG VÀ XÉT DUYỆT NGHỈ PHÉP

## PHASE 1: THIẾT LẬP CƠ SỞ DỮ LIỆU
- [x] **Task 1.1:** Tạo migration `create_timesheets_table`.
- [x] **Task 1.2:** Tạo migration `create_leave_requests_table`.
- [x] **Task 1.3:** Chạy migration trong Docker container:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```

## PHASE 2: HIỆN THỰC HÓA API
- [x] **Task 2.1:** Viết model `Timesheet.php` và `LeaveRequest.php` định nghĩa các quan hệ liên kết nhân sự.
- [x] **Task 2.2:** Xây dựng `TimesheetService` để xử lý logic check-in, check-out và thống kê công theo tháng.
- [x] **Task 2.3:** Xây dựng `LeaveRequestService` quản lý quy trình nộp đơn nghỉ và xét duyệt trạng thái đơn.
- [x] **Task 2.4:** Định nghĩa file route `routes/api/timesheet.php` liên kết các endpoint API.
