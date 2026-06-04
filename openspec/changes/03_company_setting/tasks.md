# TASKS CHECKLIST: HỆ THỐNG CẤU HÌNH CÔNG TY (IMPLEMENTATION TASKLIST)

## PHASE 1: THIẾT LẬP CƠ SỞ DỮ LIỆU & CONFIGURATION
- [x] **Task 1.1:** Tạo migration `add_sidebar_sub_name_to_company_settings_table` thêm cột `sidebar_sub_name` (nullable) nằm sau cột `sidebar_name`.
- [x] **Task 1.2:** Chạy migration bằng Laravel Sail:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```

## PHASE 2: CẬP NHẬT BACKEND CODE
- [x] **Task 2.1:** Cấu hình thuộc tính `$fillable` trong Eloquent Model `CompanySetting.php` để cho phép ghi nhận trường `sidebar_sub_name`.
- [x] **Task 2.2:** Cập nhật các quy tắc validation tại Form Request `UpdateCompanySettingRequest.php`: thêm validation rule cho `'sidebar_sub_name' => ['nullable', 'string', 'max:255']`.
- [x] **Task 2.3:** Bổ sung trường `sidebar_sub_name` vào định dạng dữ liệu trả về của API Resource `CompanySettingResource.php`.

## PHASE 3: KIỂM THỬ XÁC MINH API
- [x] **Task 3.1:** Gửi yêu cầu kiểm tra API lấy cấu hình (`GET /api/master/company-setting`), xác nhận trường `sidebar_sub_name` có mặt và trả về `null` (mặc định) thành công.
