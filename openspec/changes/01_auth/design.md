# TECHNICAL DESIGN: HỆ THỐNG XÁC THỰC (AUTHENTICATION ARCHITECTURE)

## 1. Thiết kế Cơ sở dữ liệu (Database Schema)

Để chạy được hệ thống xác thực cho Nhân viên (Employee), ta cần dựng 3 bảng dữ liệu cốt lõi đầu tiên theo thứ tự ràng buộc khóa ngoại:

### 1.1. Bảng `companies` (Doanh nghiệp)
*   Chứa thông tin của doanh nghiệp (SaaS Tenants).
*   Các trường bản địa hóa: `name_kana`, `corporate_number` (JP Touroku Bangou).

### 1.2. Bảng `departments` (Phòng ban)
*   Thuộc về một Doanh nghiệp (`company_id`).

### 1.3. Bảng `employees` (Nhân viên)
*   Thực thể chính để xác thực.
*   Bổ sung cột `password` kiểu `VARCHAR(255)` để lưu hash mật khẩu.
*   Trường `phone` và `email` bắt buộc (`NOT NULL`).

---

## 2. Cấu hình Authenticate Guard & JWT (`config/auth.php`)

Để Laravel nhận diện Model `Employee` làm lớp xác thực chính của API (JWT):
*   Cấu hình **Provider** mới: `'employees' => [ 'driver' => 'eloquent', 'model' => App\Models\Employee::class ]`
*   Cấu hình **Guard** `api` để sử dụng provider `employees` và driver `jwt`.

---

## 3. Bản đồ File triển khai (File Mapping)

```text
src/
├── app/
│   ├── Models/
│   │   ├── Company.php (BaseModel)
│   │   ├── Department.php (BaseModel)
│   │   └── Employee.php (BaseAuthenticateModel - implements JWTSubject)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── Auth/
│   │   │           └── AuthController.php (Chỉ gọi Service, trả về Response Facade)
│   │   ├── Requests/
│   │   │   └── Api/
│   │   │       └── Auth/
│   │   │           └── LoginRequest.php (Dùng RequestTrait)
│   │   └── Resources/
│   │       └── Api/
│   │           └── Auth/
│   │               ├── EmployeeResource.php
│   │               └── CompanyResource.php
│   ├── Services/
│   │   └── Api/
│   │       └── Auth/
│   │           └── AuthService.php (Xử lý JWT login logic, throws BusinessException)
│   └── Repositories/
│       └── Api/
│           └── Employee/
│               ├── EmployeeRepository.php
│               └── EmployeeRepositoryEloquent.php
├── database/
│   ├── migrations/
│   │   ├── 2026_06_01_000001_create_companies_table.php
│   │   ├── 2026_06_01_000002_create_departments_table.php
│   │   └── 2026_06_01_000003_create_employees_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php (Gọi CompanyEmployeeSeeder)
│       └── CompanyEmployeeSeeder.php (Tạo 1 công ty, 1 phòng ban và 1 nhân viên quản trị mẫu để đăng nhập test)
└── routes/
    └── api/
        └── auth.php (Nhóm route /api/auth/*)
```
