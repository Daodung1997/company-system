# PROPOSAL: HỆ THỐNG CHẤM CÔNG VÀ XÉT DUYỆT NGHỈ PHÉP (TIMESHEETS & LEAVE REQUESTS)

## 1. Yêu cầu nghiệp vụ
Hệ thống cần quản lý thời gian làm việc hàng ngày của nhân sự và quy trình xin nghỉ phép trực tuyến để tự động hóa tính công lương và đảm bảo tính tuân thủ giờ làm việc.
*   **Chấm công cá nhân**: Cho phép nhân viên check-in/check-out qua web. Lưu vết chính xác giờ làm việc, cảnh báo đi muộn/về sớm.
*   **Xét duyệt nghỉ phép**: Quy trình nộp đơn xin nghỉ phép trực tuyến, tự động chuyển đến cấp quản lý trực tiếp phê duyệt kèm lý do và ghi chú.

## 2. Giải pháp kỹ thuật

### 2.1. Quản lý Chấm công
*   **Cơ chế**: Sử dụng bảng `timesheets` để ghi nhận sự kiện hàng ngày.
*   **Trạng thái công**: Tự động tính toán trạng thái công (`ON_TIME`, `LATE`, `EARLY_LEAVE`, `ABSENT`) dựa trên giờ check-in/out so với cấu hình làm việc chuẩn (`WorkingHourConfig`).

### 2.2. Quy trình nghỉ phép
*   **Cơ chế**: Nhân viên nộp đơn xin nghỉ phép kèm minh chứng (`attachment_path`). Đơn được lưu vào bảng `leave_requests` ở trạng thái `PENDING`.
*   **Phê duyệt**: Quản trị viên sử dụng API `/api/leave-requests/{id}/approve` để duyệt (`APPROVED`) hoặc từ chối (`REJECTED`) đơn, kèm lý do của người phê duyệt (`approver_note`).
*   **Liên kết**: Đơn nghỉ phép đã được phê duyệt sẽ tự động đồng bộ hóa trạng thái vắng mặt hợp lệ trong bảng chấm công của những ngày tương ứng.
