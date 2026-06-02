# MODULE 2: QUẢN LÝ NHÂN SỰ & THÔNG TIN GIA ĐÌNH
## OpenSpec - Đặc tả chức năng chi tiết

---

## 1. Tổng quan
Module quản lý nhân sự (HRM) cho phép CRUD thông tin hồ sơ nhân viên và thân nhân (gia đình).
Hỗ trợ bản địa hóa Việt - Nhật (full_name, full_name_kana, romaji_name).

### Đối tượng (Entities):
- **Employee** - Nhân viên (đã có sẵn từ Module 1)
- **EmployeeRelative** - Thân nhân nhân viên (MỚI)

---

## 2. Database Schema

### 2.1. Table `employee_relatives`

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `code` | VARCHAR(20) | UNIQUE, NULLABLE | Mã tự sinh (REL-xxxxx) |
| `employee_id` | BIGINT UNSIGNED | FK → employees.id, CASCADE | Nhân viên chủ hồ sơ |
| `relationship` | VARCHAR(50) | REQUIRED | Quan hệ: SPOUSE, CHILD, PARENT, SIBLING, OTHER |
| `full_name` | VARCHAR(150) | REQUIRED | Họ tên thân nhân |
| `full_name_kana` | VARCHAR(150) | NULLABLE | Tên Katakana (JP) |
| `date_of_birth` | DATE | NULLABLE | Ngày sinh |
| `gender` | VARCHAR(10) | NULLABLE | MALE, FEMALE, OTHER |
| `phone` | VARCHAR(20) | REQUIRED | Số ĐT liên hệ khẩn cấp (BẮT BUỘC) |
| `email` | VARCHAR(150) | NULLABLE | Email (tùy chọn) |
| `identity_number` | VARCHAR(50) | NULLABLE | Số CCCD/My Number |
| `occupation` | VARCHAR(150) | NULLABLE | Nghề nghiệp |
| `address` | VARCHAR(500) | NULLABLE | Địa chỉ |
| `is_emergency_contact` | BOOLEAN | DEFAULT FALSE | Liên hệ khẩn cấp |
| `is_dependent` | BOOLEAN | DEFAULT FALSE | Người phụ thuộc (thuế) |
| `notes` | TEXT | NULLABLE | Ghi chú |
| `created_by` | VARCHAR(50) | NULLABLE | |
| `updated_by` | VARCHAR(50) | NULLABLE | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Indexes:** `[employee_id, relationship]`, `[employee_id, is_emergency_contact]`

---

## 3. API Endpoints

### 3.1. Quản lý Nhân sự (Employees)

| Method | URL | Auth | Mô tả |
|--------|-----|------|-------|
| GET | `/api/employees` | ✅ | Danh sách nhân sự (phân trang, lọc) |
| GET | `/api/employees/{id}` | ✅ | Chi tiết hồ sơ nhân sự |
| POST | `/api/employees` | ✅ | Tạo mới nhân sự |
| PUT | `/api/employees/{id}` | ✅ | Cập nhật hồ sơ nhân sự |
| DELETE | `/api/employees/{id}` | ✅ | Xóa nhân sự (soft delete) |

### 3.2. Quản lý Thân nhân (Employee Relatives)

| Method | URL | Auth | Mô tả |
|--------|-----|------|-------|
| GET | `/api/employees/{id}/relatives` | ✅ | DS thân nhân của nhân viên |
| POST | `/api/employees/{id}/relatives` | ✅ | Thêm thân nhân |
| PUT | `/api/employees/{id}/relatives/{relativeId}` | ✅ | Cập nhật thân nhân |
| DELETE | `/api/employees/{id}/relatives/{relativeId}` | ✅ | Xóa thân nhân |

---

## 4. Chi tiết API

### 4.1. GET /api/employees - Danh sách nhân sự

**Query Parameters:**
```
page=1
per_page=15
search=<tìm theo tên, email, phone, mã nhân viên>
company_id=<lọc theo công ty>
department_id=<lọc theo phòng ban>
status=ACTIVE|INACTIVE|PROBATION
role=ADMIN|MANAGER|STAFF
sort_by=full_name|join_date|created_at
sort_order=asc|desc
```

**Response (200):**
```json
{
  "code": 200,
  "data": {
    "items": [
      {
        "id": 1,
        "code": "EMP-00001",
        "full_name": "Nguyễn Văn Quản Trị",
        "full_name_kana": "グエン ヴァン クアントリ",
        "email": "admin@compliance.vn",
        "phone": "0987654321",
        "role": "ADMIN",
        "status": "ACTIVE",
        "join_date": "2024-01-15",
        "company": { "id": 1, "name": "Công ty ABC" },
        "department": { "id": 1, "name": "Phòng IT" },
        "relatives_count": 3
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 42,
      "last_page": 3
    }
  }
}
```

### 4.2. GET /api/employees/{id} - Chi tiết nhân sự

**Response (200):** Trả đầy đủ thông tin Employee + kèm danh sách Relatives.

### 4.3. POST /api/employees - Tạo nhân sự

**Request Body:**
```json
{
  "company_id": "integer | required",
  "department_id": "integer | required",
  "full_name": "string | required | max:150",
  "full_name_kana": "string | nullable | max:150",
  "romaji_name": "string | nullable | max:150",
  "email": "string | required | email | unique:employees",
  "phone": "string | required | max:20",
  "password": "string | required | min:8 | max:50 | passwordStrength",
  "identity_type": "string | in:CCCD,MY_NUMBER,ZAIRYU_CARD,PASSPORT",
  "identity_number": "string | nullable | unique:employees",
  "zairyu_card_expiry": "date | nullable",
  "tax_code": "string | nullable | max:50",
  "social_insurance_code": "string | nullable | max:50",
  "pension_number": "string | nullable | max:50",
  "employment_insurance_number": "string | nullable | max:50",
  "bank_code": "string | nullable | max:10",
  "bank_branch_code": "string | nullable | max:10",
  "bank_account_type": "string | nullable | max:50",
  "bank_account_number": "string | nullable | max:50",
  "bank_account_holder_kana": "string | nullable | max:150",
  "role": "string | in:ADMIN,MANAGER,STAFF | default:STAFF",
  "dependents_count": "integer | default:0",
  "address_registered": "string | nullable | max:500",
  "address_current": "string | nullable | max:500",
  "status": "string | in:ACTIVE,INACTIVE,PROBATION | default:PROBATION",
  "join_date": "date | required"
}
```

### 4.4. POST /api/employees/{id}/relatives - Thêm thân nhân

**Request Body:**
```json
{
  "relationship": "string | required | in:SPOUSE,CHILD,PARENT,SIBLING,OTHER",
  "full_name": "string | required | max:150",
  "full_name_kana": "string | nullable | max:150",
  "date_of_birth": "date | nullable",
  "gender": "string | nullable | in:MALE,FEMALE,OTHER",
  "phone": "string | required | max:20",
  "email": "string | nullable | email | max:150",
  "identity_number": "string | nullable | max:50",
  "occupation": "string | nullable | max:150",
  "address": "string | nullable | max:500",
  "is_emergency_contact": "boolean | default:false",
  "is_dependent": "boolean | default:false",
  "notes": "string | nullable"
}
```

---

## 5. Business Rules

1. **SĐT thân nhân bắt buộc:** Mỗi thân nhân phải có ít nhất số điện thoại liên hệ.
2. **Liên hệ khẩn cấp:** Khuyến nghị mỗi nhân viên nên có ít nhất 1 thân nhân được đánh dấu `is_emergency_contact = true`.
3. **Người phụ thuộc:** Khi `is_dependent = true`, cần ghi nhận cho mục đích tính thuế TNCN. `dependents_count` trên Employee sẽ được đồng bộ.
4. **Soft Delete:** Nhân viên bị xóa sẽ chuyển `status = INACTIVE`, không xóa vật lý.
5. **Phân quyền:**
   - `ADMIN`: CRUD tất cả nhân viên trong công ty.
   - `MANAGER`: Xem nhân viên trong phòng ban; sửa thông tin nhân viên cấp dưới.
   - `STAFF`: Chỉ xem/sửa hồ sơ cá nhân.
