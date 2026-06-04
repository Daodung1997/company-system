# TASKS CHECKLIST: HỆ THỐNG QUẢN LÝ TÀI LIỆU VÀ HỒ SƠ LƯU TRỮ

## PHASE 1: THIẾT LẬP CƠ SỞ DỮ LIỆU
- [x] **Task 1.1:** Tạo migration `create_documents_table`.
- [x] **Task 1.2:** Chạy migration trong Docker container:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```

## PHASE 2: HIỆN THỰC HÓA API
- [x] **Task 2.1:** Thiết lập cấu hình lưu trữ Laravel Storage (`config/filesystems.php`).
- [x] **Task 2.2:** Viết model `Document.php` định nghĩa accessor `getUrlAttribute` tự động giải quyết đường dẫn ảnh xem trước.
- [x] **Task 2.3:** Xây dựng `DocumentService` thực hiện tải tệp vật lý lên đĩa và lưu thông tin vào DB.
- [x] **Task 2.4:** Tạo `DocumentController` và định nghĩa tệp route `/api/documents/*`.
