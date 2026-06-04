# TECHNICAL DESIGN: HỆ THỐNG CẤU HÌNH CÔNG TY (COMPANY SETTINGS ARCHITECTURE)

## 1. Thiết kế Cơ sở dữ liệu (Database Schema)

Để lưu trữ cấu hình tên hiển thị phụ dưới Sidebar, ta thực hiện thay đổi cấu trúc bảng `company_settings`:

### 1.1. Migration `add_sidebar_sub_name_to_company_settings_table`
* Thêm cột `sidebar_sub_name` (nullable) nằm phía sau cột `sidebar_name`.
```php
Schema::table('company_settings', function (Blueprint $table) {
    $table->string('sidebar_sub_name')->nullable()->after('sidebar_name');
});
```

---

## 2. Bản đồ File triển khai (File Mapping)

Các file liên quan đến cấu hình hệ thống trên Backend:

```text
src/
├── app/
│   ├── Models/
│   │   └── CompanySetting.php (Thêm sidebar_sub_name vào $fillable)
│   ├── Http/
│   │   ├── Requests/
│   │   │   └── Master/
│   │   │       └── UpdateCompanySettingRequest.php (Thêm validation rules cho sidebar_sub_name)
│   │   └── Resources/
│   │       └── Master/
│   │           └── CompanySettingResource.php (Serialization sidebar_sub_name)
├── database/
│   └── migrations/
│       └── 2026_06_04_161052_add_sidebar_sub_name_to_company_settings_table.php (Thêm cột sidebar_sub_name)
```

---

## 3. Quy trình xử lý Ảnh mặc định (Image Fallback Logic)

Khi xóa ảnh, API của Backend lưu giá trị `NULL` vào DB:
* `logo_path` = `NULL`
* `background_path` = `NULL`

Khi client yêu cầu dữ liệu, hệ thống trả về giá trị `NULL` qua API Resource.
* Phía Frontend/Client chịu trách nhiệm hiển thị các asset tĩnh mặc định nếu giá trị là `NULL`/rỗng.
