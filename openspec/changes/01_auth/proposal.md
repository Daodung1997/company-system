# CHANGE PROPOSAL: HỆ THỐNG ĐĂNG NHẬP & XÁC THỰC (AUTHENTICATION SYSTEM)

## 1. Lý do và Mục tiêu thay đổi
Để đưa hệ thống **Quản lý tuân thủ & Hồ sơ Pháp lý Doanh nghiệp (Compliance System)** vào vận hành, chức năng đăng nhập và xác thực (Authentication) là module cốt lõi hàng đầu. Hệ thống cần một giải pháp bảo mật sử dụng cơ chế **JWT (JSON Web Token)** để cấp phát token và định danh người dùng trong mọi cuộc gọi API.

Đối tượng đăng nhập chính là **Employee (Nhân viên)** trực thuộc các Doanh nghiệp khác nhau (SaaS Multi-tenant). Lập trình viên Backend cần chuyển đổi cơ cấu đăng nhập mặc định của Base Project từ `User` sang `Employee`, đồng thời hỗ trợ đăng nhập linh hoạt bằng cả **Email** hoặc **Số điện thoại (Phone)** - hai thông tin cực kỳ quan trọng và bắt buộc đã được cấu hình từ trước.

## 2. Phạm vi ảnh hưởng
*   **Database:**
    *   Tạo bảng `companies` (Doanh nghiệp) để làm gốc liên kết.
    *   Tạo bảng `departments` (Phòng ban).
    *   Tạo bảng `employees` (Nhân sự) đóng vai trò là thực thể xác thực chính, có thêm trường `password` mã hóa bcrypt.
*   **Routing:** Cấu hình nhóm Route API xác thực mới `/api/auth/` thay thế cho cấu hình ViecVat mặc định.
*   **Models:** Khởi tạo `Company`, `Department`, `Employee` (Kế thừa `BaseAuthenticateModel` để kế thừa JWT).
*   **Services & Repositories:** Triển khai `EmployeeService` và `EmployeeRepository`.
*   **Controllers:** Tạo `AuthController` để xử lý đăng nhập, đăng xuất và lấy thông tin cá nhân hiện tại.
*   **Request & Resources:** Tạo các FormRequest validate đầu vào và JsonResource định dạng đầu ra.

## 3. Các kịch bản kiểm thử mẫu (Usecase scenarios)
*   **Scenario 1: Đăng nhập thành công bằng Email.**
    *   `POST /api/auth/login` với `{ "username": "admin@company.com", "password": "password123" }`.
    *   *Kết quả:* HTTP 200, trả về Token JWT và thông tin hồ sơ nhân viên.
*   **Scenario 2: Đăng nhập thành công bằng Số điện thoại.**
    *   `POST /api/auth/login` với `{ "username": "0987654321", "password": "password123" }`.
    *   *Kết quả:* HTTP 200, trả về Token JWT.
*   **Scenario 3: Đăng nhập thất bại do thông tin không chính xác.**
    *   `POST /api/auth/login` với sai mật khẩu hoặc tài khoản không tồn tại.
    *   *Kết quả:* HTTP 401, thông báo lỗi `Tài khoản hoặc mật khẩu không chính xác`.
