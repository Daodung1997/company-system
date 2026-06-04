# FUNCTIONAL SPECIFICATION: CẤU HÌNH THÔNG TIN CÔNG TY (COMPANY SETTINGS SPEC)

## 1. API: Xem cấu hình công ty (`GET /api/master/company-setting`)
Lấy cấu hình tùy chỉnh giao diện và thông tin pháp lý của doanh nghiệp hiện tại.

*   **URL:** `/api/master/company-setting`
*   **Method:** `GET`
*   **Auth Required:** Có (Header `Authorization: Bearer <token>`)

### Response Schema (Success 200)
```json
{
  "code": 200,
  "data": {
    "id": 1,
    "company_id": null,
    "company_name": "Công ty Cổ phần Giải pháp Công nghệ Việt Nam",
    "company_name_kana": "VIETNAM TECH SOLUTIONS",
    "tax_code": "0109283746",
    "corporate_number": "GPKD: 1234567890123",
    "address_registered": "Tầng 12, Tòa nhà Keangnam Landmark 72...",
    "legal_representative": "Nguyễn Văn A",
    "representative_title": "Tổng Giám đốc",
    "representative_id_number": "001092837465",
    "representative_id_date": "2020-05-15",
    "representative_id_place": "Cục Cảnh sát Quản lý hành chính...",
    "charter_capital": "10.000.000.000 VNĐ",
    "phone_number": "02439876543",
    "email": "contact@techsolutions.com.vn",
    "fax": "02439876544",
    "postcode": "100000",
    "address": "Tòa nhà Keangnam Landmark 72...",
    "website": "https://techsolutions.com.vn",
    "hanko_seal_path": "/seals/default_seal.png",
    "logo_path": null,
    "background_path": "http://localhost/storage/documents/1780563697_8cHoUeknBcBEy8Fs.jpg",
    "sidebar_name": "SC SOFT",
    "sidebar_sub_name": "Hệ thống Quản lý Tuân thủ",
    "slogan_1": "Tiên phong Công nghệ - Kiến tạo Tương lai",
    "slogan_2": "Hệ thống Quản lý Tuân thủ Doanh nghiệp Compliance System",
    "slogan_3": "Nâng tầm Quản trị, Tối ưu Vận hành",
    "created_at": "2026-06-04 15:53:20",
    "updated_at": "2026-06-04 16:07:53"
  }
}
```

---

## 2. API: Cập nhật cấu hình công ty (`PUT /api/master/company-setting`)
Cập nhật cấu hình thông tin doanh nghiệp, logo, hình nền đăng nhập, tên hiển thị sidebar, tên phụ sidebar.

*   **URL:** `/api/master/company-setting`
*   **Method:** `PUT`
*   **Auth Required:** Có (Header `Authorization: Bearer <token>`)

### Request Body Schema
```json
{
  "company_name": "string | required | max:255",
  "logo_path": "string | nullable | max:255",
  "background_path": "string | nullable | max:255",
  "sidebar_name": "string | nullable | max:255",
  "sidebar_sub_name": "string | nullable | max:255",
  ...
}
```

### Quy tắc nghiệp vụ (Business Rules)
1. Để xóa logo hoặc hình nền nhằm khôi phục lại mặc định, Frontend truyền `"logo_path": ""` (hoặc `null`) và `"background_path": ""` (hoặc `null`) trong request body.
2. Nếu `sidebar_sub_name` rỗng hoặc không truyền, giao diện Sidebar phía client sẽ tự động hiển thị tiêu đề phụ mặc định.
