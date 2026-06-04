# TASKS CHECKLIST: HỆ THỐNG QUẢN LÝ HỢP ĐỒNG

## PHASE 1: THIẾT LẬP CƠ SỞ DỮ LIỆU
- [x] **Task 1.1:** Tạo migration `create_contracts_table`.
- [x] **Task 1.2:** Chạy migration trong Docker container:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```

## PHASE 2: HIỆN THỰC HÓA API
- [x] **Task 2.1:** Viết model `Contract.php` định nghĩa các quan hệ đa hình với `Document` và liên kết với `Employee`, `Company`.
- [x] **Task 2.2:** Xây dựng `ContractService` xử lý logic tính toán làm thêm giờ khoán và tự điền thông tin Bên A từ cấu hình doanh nghiệp.
- [x] **Task 2.3:** Tạo `ContractController` triển khai các hành động CRUD và API kết xuất PDF hợp đồng có đính kèm con dấu doanh nghiệp.
- [x] **Task 2.4:** Thiết lập route `/api/contracts/*` phục vụ các màn hình quản trị hợp đồng.
