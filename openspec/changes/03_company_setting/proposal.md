# PROPOSAL: HỆ THỐNG CẤU HÌNH CÔNG TY (COMPANY SETTINGS ENHANCEMENT)

## 1. Yêu cầu cải tiến
Hệ thống cần bổ sung và tối ưu hóa hai tính năng cốt lõi trong phần cấu hình thông tin doanh nghiệp (Company Settings):
1. **Khôi phục ảnh mặc định (Revert Uploaded Images to Default):**
   * Cho phép người dùng xóa các ảnh (logo công ty, hình nền đăng nhập) đã được tải lên trước đó.
   * Khi thực hiện xóa và lưu cấu hình, hệ thống sẽ khôi phục lại các tài nguyên hình ảnh mặc định ban đầu.
2. **Thêm trường Tên hiển thị phụ dưới Sidebar (Sidebar Sub-name):**
   * Bổ sung trường nhập liệu phụ dạng text cho tên hiển thị ở Sidebar.
   * Tên phụ này sẽ được hiển thị ngay bên dưới tên công ty chính ở Sidebar của hệ thống.

## 2. Giải pháp kỹ thuật

### 2.1. Khôi phục ảnh mặc định
* **Cơ chế:** Khi người dùng nhấn nút "Xóa ảnh" trên giao diện, các trường `logo_path` hoặc `background_path` trong dữ liệu gửi lên (Request Payload) sẽ được gán giá trị chuỗi rỗng `""` (hoặc `null`).
* **Backend:** Do API cập nhật thông tin cài đặt đã cho phép các trường này nhận giá trị `nullable`, việc gửi `null` hoặc chuỗi rỗng lên sẽ cập nhật cơ sở dữ liệu về `NULL`.
* **Frontend:** Trên giao diện hiển thị (Sidebar, màn hình đăng nhập Auth layout), hệ thống sẽ kiểm tra nếu `logo_path` hoặc `background_path` rỗng/null, nó sẽ tự động render các tệp ảnh mặc định của hệ thống (`/images/login-bg.png` cho background, logo mặc định SVG/tệp ảnh cho logo).

### 2.2. Trường Tên hiển thị phụ dưới Sidebar (`sidebar_sub_name`)
* **Cơ sở dữ liệu (Database):** Thêm trường `sidebar_sub_name` kiểu `VARCHAR(255)` nullable vào bảng `company_settings`, định vị sau cột `sidebar_name`.
* **Backend:**
  * Thêm `sidebar_sub_name` vào thuộc tính `$fillable` của Eloquent Model `CompanySetting`.
  * Cập nhật Form Request `UpdateCompanySettingRequest` với quy tắc validation: `sidebar_sub_name => ['nullable', 'string', 'max:255']`.
  * Cập nhật API Resource `CompanySettingResource` để trả về trường dữ liệu mới này cho phía Frontend.
* **Frontend:**
  * Cập nhật form state (`form.sidebar_sub_name` và `formErrors.sidebar_sub_name`) trong trang quản lý cấu hình công ty.
  * Thêm input field "Tên hiển thị phụ ở Sidebar" (Sub-name) chia cột 50-50 với trường "Tên hiển thị ở Sidebar" hiện tại.
  * Cập nhật thành phần `AppSidebar.vue` để hiển thị `sidebar_sub_name` nếu có cấu hình, nếu không có sẽ tự động fallback về tiêu đề mặc định của hệ thống (`menu.industrialDashboard`).
