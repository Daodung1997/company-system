# MODULE 5: QUẢN LÝ HỢP ĐỒNG (CONTRACT MANAGEMENT)
## OpenSpec - Đặc tả chức năng chi tiết

---

## 1. Tổng quan
Module quản lý hợp đồng lưu trữ và xử lý các loại hợp đồng lao động của nhân viên và hợp đồng thương mại/đối tác. Module này kiểm soát chặt chẽ các điều khoản tuân thủ làm thêm giờ (36 Agreement theo luật Nhật Bản hoặc giới hạn tăng ca của Luật Lao động Việt Nam) và tự động đồng bộ hóa hồ sơ với công cụ tính lương.

### Đối tượng (Entities):
- **Contract** - Thông tin chi tiết hợp đồng (mã số, đối tác, giá trị, lương, chế độ làm thêm giờ).
- **Document** - Bản scan hợp đồng đính kèm (EDM).

---

## 2. Database Schema

### 2.1. Table `contracts`
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `employee_id` | BIGINT UNSIGNED | FK → employees.id, NULLABLE | Nhân viên thụ hưởng (nếu là HĐ lao động) |
| `company_id` | BIGINT UNSIGNED | FK → companies.id | Công ty chủ quản bên A |
| `company_name` | VARCHAR(255) | REQUIRED | Tên pháp nhân bên A |
| `company_tax_code` | VARCHAR(50) | REQUIRED | Mã số thuế bên A |
| `contract_code` | VARCHAR(100) | UNIQUE, REQUIRED | Mã số hợp đồng |
| `type` | VARCHAR(30) | REQUIRED | Loại: LABOR (Lao động), COMMERCIAL (Thương mại), VENDOR (Nhà cung cấp) |
| `employment_type` | VARCHAR(30) | NULLABLE | Hình thức: SEISHAIN, KEIYAKUSHAIN, HAKEN, ARUBAITO |
| `is_36_agreement_applicable` | BOOLEAN | DEFAULT FALSE | Có áp dụng thỏa thuận tăng ca 36 hay không |
| `overtime_allowance_included` | BOOLEAN | DEFAULT FALSE | Lương gộp làm thêm giờ cố định |
| `included_overtime_hours` | INT | NULLABLE | Số giờ làm thêm khoán gộp trong tháng |
| `probation_period_months` | INT | NULLABLE | Số tháng thử việc |
| `sign_date` | DATE | REQUIRED | Ngày ký kết |
| `start_date` | DATE | REQUIRED | Ngày hiệu lực |
| `end_date` | DATE | NULLABLE | Ngày hết hạn |
| `value` | DECIMAL(15,2) | REQUIRED | Giá trị hợp đồng / Lương cơ bản |
| `status` | VARCHAR(20) | REQUIRED | Trạng thái: ACTIVE, EXPIRED, TERMINATED, PENDING |

---

## 3. API Endpoints

### 3.1. Quản lý Hợp đồng (Contracts)
| Method | URL | Auth | Mô tả |
|--------|-----|------|-------|
| GET | `/api/contracts` | ✅ | Danh sách hợp đồng (lọc theo loại, trạng thái, tìm kiếm) |
| POST | `/api/contracts` | ✅ | Ký kết / Đăng ký hợp đồng mới |
| GET | `/api/contracts/{id}` | ✅ | Chi tiết thông tin hợp đồng |
| PUT | `/api/contracts/{id}` | ✅ | Cập nhật điều khoản hợp đồng |
| DELETE | `/api/contracts/{id}` | ✅ | Xóa hợp đồng |
| GET | `/api/contracts/{id}/export-pdf` | ✅ | Kết xuất file mẫu hợp đồng dạng PDF |

---

## 4. Chi tiết API cốt lõi

### 4.1. GET /api/contracts - Danh sách hợp đồng
*   **Query Parameters:**
```
page=1
per_page=15
search=<mã hợp đồng hoặc tên nhân viên/đối tác>
type=LABOR|COMMERCIAL
status=ACTIVE|EXPIRED
```
*   **Response (200):**
```json
{
  "code": 200,
  "data": {
    "items": [
      {
        "id": 8,
        "contract_code": "HDLD-2026-0009",
        "employee": { "id": 5, "full_name": "Trần Thị B" },
        "type": "LABOR",
        "value": 15000000.00,
        "start_date": "2026-01-01",
        "end_date": "2027-01-01",
        "status": "ACTIVE"
      }
    ],
    "pagination": {
      "total": 12,
      "per_page": 15,
      "current_page": 1
    }
  }
}
```

### 4.2. POST /api/contracts - Thêm hợp đồng
*   **Request Body Schema:**
```json
{
  "employee_id": 5,
  "type": "LABOR",
  "employment_type": "SEISHAIN",
  "contract_code": "HDLD-2026-0009",
  "is_36_agreement_applicable": true,
  "overtime_allowance_included": false,
  "sign_date": "2026-01-01",
  "start_date": "2026-01-01",
  "end_date": "2027-01-01",
  "value": 15000000.00,
  "status": "ACTIVE"
}
```
*   **Response (201):** Trả về đối tượng `ContractResource` đã tạo thành công.
```json
{
  "code": 201,
  "data": {
    "id": 8,
    "contract_code": "HDLD-2026-0009",
    "status": "ACTIVE"
  }
}
```

---

## 5. Mối liên hệ với Cấu hình Công ty (Company Settings Integration)
*   **Điền tự động thông tin Bên A (Party A Auto-Fill):** Khi tạo mới hợp đồng lao động hoặc thương mại, toàn bộ các thông tin pháp lý của doanh nghiệp (Tên công ty bên A, mã số thuế bên A, địa chỉ đăng ký kinh doanh, người đại diện pháp luật và chức vụ) sẽ được truy xuất tự động từ cấu hình công ty (`CompanySetting`) để điền vào phần thông tin Bên A của hợp đồng.
*   **Xuất bản ký số bằng Đóng dấu (Hanko Seal Signing):** Khi xuất hợp đồng ra PDF để tải về, hệ thống sẽ sử dụng tệp ảnh con dấu đại diện doanh nghiệp (`hanko_seal_path`) từ cấu hình công ty để in trực tiếp vào khu vực chữ ký đại diện của Bên A trên bản in PDF hợp đồng.

