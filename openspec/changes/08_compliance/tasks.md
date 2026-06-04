# TASKS CHECKLIST: ĐỘNG CƠ KIỂM SOÁT TUÂN THỦ

## PHASE 1: THIẾT LẬP CƠ SỞ DỮ LIỆU
- [x] **Task 1.1:** Tạo migration `create_compliance_issues_table`.
- [x] **Task 1.2:** Chạy migration trong Docker container:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```

## PHASE 2: HIỆN THỰC HÓA API
- [x] **Task 2.1:** Tạo model `ComplianceIssue.php` và liên kết với các thực thể liên quan.
- [x] **Task 2.2:** Xây dựng `ComplianceService` triển khai logic phân tích luật đối soát chấm công quá giờ, giao dịch thiếu hóa đơn và kiểm tra thông tin pháp lý của doanh nghiệp từ cài đặt công ty.
- [x] **Task 2.3:** Triển khai `ComplianceController` cung cấp API lấy danh sách vi phạm, trigger quét và giải quyết sự cố.
- [x] **Task 2.4:** Định nghĩa file route `/api/compliance/*`.
