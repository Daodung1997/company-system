---
description: Debug failed unit tests by checking specs and fixing code/tests
---

# Workflow: Debug Failed Unit Tests

Thực hiện theo các bước sau để xử lý khi unit test bị fail:

1.  **Chạy lại Test Case**:
    -   Xác định file test và method đang fail.
    -   Chạy lệnh phpunit để tái hiện lỗi: `vendor/bin/phpunit path/to/test/file --filter methodName`
    -   Phân tích lỗi (Error output, Stack trace).

2.  **Đối chiếu Specification**:
    -   Xác định module tin quan (ví dụ: `auth`, `user`, `service`...).
    -   Đọc file spec tại: `docs/features/<module>/01_apis/index.md` (hoặc thư mục API tương ứng trong module).
    -   So sánh logic trong Spec và logic trong code/test hiện tại.

3.  **Nghiên cứu & Sửa lỗi**:
    -   Luôn đảm bảo rules trong coding-conventions.md
    -   Nếu Spec đúng, Code sai -> Sửa API Code.
    -   Nếu Spec đúng, Test sai -> Sửa Test Case.
    -   Nếu Code đúng, Spec thiếu/sai -> Cập nhật Spec (nếu được phép) hoặc báo lại User.
    -   Đảm bảo tuân thủ `coding-conventions.md`.
   
4.  **Verification**:
    -   Chạy lại test case để verify fix: `vendor/bin/phpunit path/to/test/file --filter methodName`
    -   Chạy toàn bộ file test để đảm bảo không regression: `vendor/bin/phpunit path/to/test/file`