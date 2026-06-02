# TASKS CHECKLIST: HỆ THỐNG XÁC THỰC (IMPLEMENTATION TASKLIST)

## PHASE 1: THIẾT LẬP CƠ SỞ DỮ LIỆU (DATABASE SETUP)
- [x] **Task 1.1:** Tạo migration `create_companies_table` chứa thông tin công ty SaaS Tenant.
- [x] **Task 1.2:** Tạo migration `create_departments_table` chứa thông tin phòng ban trực thuộc.
- [x] **Task 1.3:** Tạo migration `create_employees_table` chứa thông tin nhân viên cốt lõi (với trường `phone` NOT NULL, `email` NOT NULL UNIQUE và `password`).
- [x] **Task 1.4:** Tạo seeder `CompanyEmployeeSeeder.php` để khởi tạo dữ liệu chạy thử:
    *   1 Doanh nghiệp Việt Nam mẫu.
    *   1 Phòng ban IT.
    *   1 Nhân viên quản trị cấp cao để phục vụ đăng nhập test (`admin@compliance.vn` / mật khẩu `password123` / số điện thoại `0987654321`).
- [x] **Task 1.5:** Cập nhật `DatabaseSeeder.php` để tự động gọi seeder trên.

## PHASE 2: KHỞI TẠO ĐỐI TƯỢNG & KHO DỮ LIỆU (MODELS & REPOSITORIES)
- [x] **Task 2.1:** Tạo Model `Company.php` kế thừa `BaseModel`. Định nghĩa `$fillable` và quan hệ `departments()`, `employees()`.
- [x] **Task 2.2:** Tạo Model `Department.php` kế thừa `BaseModel`. Định nghĩa quan hệ `company()`, `employees()`.
- [x] **Task 2.3:** Tạo Model `Employee.php` kế thừa `BaseAuthenticateModel` (implements `JWTSubject`). Thiết lập ẩn mật khẩu (`$hidden`) và casts mật khẩu (`password => hashed`).
- [x] **Task 2.4:** Tạo Repository interface `EmployeeRepository` kế thừa `RepositoryInterface`.
- [x] **Task 2.5:** Tạo lớp triển khai `EmployeeRepositoryEloquent` kế thừa `BaseRepository` và implement `EmployeeRepository`.
- [x] **Task 2.6:** Khai báo liên kết Repository mới trong `AppServiceProvider.php` (hoặc `RepositoryServiceProvider`).

## PHASE 3: HIỆN THỰC HÓA XỬ LÝ ĐĂNG NHẬP (AUTH LOGIC & CONFIGURATION)
- [x] **Task 3.1:** Chỉnh sửa cấu hình `config/auth.php`: thiết lập `api` guard sử dụng driver `jwt` và provider `employees`.
- [x] **Task 3.2:** Tạo `LoginRequest.php` kế thừa FormRequest và dùng `RequestTrait` để tự động hóa validate `username` (email hoặc số điện thoại) và `password`.
- [x] **Task 3.3:** Tạo `CompanyResource.php` và `EmployeeResource.php` định dạng dữ liệu trả về an toàn.
- [x] **Task 3.4:** Tạo `AuthService.php` kế thừa `AbstractService`:
    *   Xử lý logic xác thực credentials.
    *   Sử dụng JWT để sinh token.
    *   Throw `BusinessException(ExceptionCode::AUTH_INVALID_CREDENTIALS)` nếu thất bại.
- [x] **Task 3.5:** Tạo `AuthController.php` gọi `AuthService` và trả về `Response::success` hoặc `Response::created`.
- [x] **Task 3.6:** Khởi tạo file route `routes/api/auth.php` định nghĩa các API `/login`, `/logout`, `/me`. Nhúng vào file route chính `routes/api.php`.

## PHASE 4: KIỂM THỬ VÀ HOÀN THÀNH (VERIFICATION & TESTING)
- [x] **Task 4.1:** Chạy lệnh migration và seeding trên container Docker:
    ```bash
    ./vendor/bin/sail artisan migrate:refresh --seed
    ```
- [x] **Task 4.2:** Tạo Feature Test `tests/Feature/Auth/LoginTest.php` kiểm tra toàn bộ kịch bản (Success Email, Success Phone, Validation Error, Wrong Password, Get Me, Logout).
- [x] **Task 4.3:** Chạy PHPUnit và bảo đảm toàn bộ tests đều pass 100%.
    ```bash
    ./vendor/bin/sail test --filter=LoginTest
    ```
