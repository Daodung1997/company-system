# TASKS CHECKLIST: HỆ THỐNG QUẢN LÝ THU CHI VÀ CHI PHÍ

## PHASE 1: THIẾT LẬP CƠ SỞ DỮ LIỆU
- [x] **Task 1.1:** Tạo migration `create_transactions_table`.
- [x] **Task 1.2:** Chạy migration trong Docker container:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```

## PHASE 2: HIỆN THỰC HÓA API
- [x] **Task 2.1:** Tạo model `Transaction.php` với thuộc tính tự động ép kiểu dữ liệu tài chính (casts decimal).
- [x] **Task 2.2:** Xây dựng `TransactionService` triển khai quy tắc tính toán phân tách trước thuế, tiền thuế và kết nối kiểm tra chéo mã số thuế doanh nghiệp.
- [x] **Task 2.3:** Triển khai `TransactionController` xử lý yêu cầu thêm, xem và cập nhật giao dịch.
- [x] **Task 2.4:** Định nghĩa file route `routes/api/transaction.php`.
