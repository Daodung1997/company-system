# MODULE 8: ĐỘNG CƠ KIỂM SOÁT TUÂN THỦ (COMPLIANCE ENGINE)
## OpenSpec - Đặc tả chức năng chi tiết

---

## 1. Tổng quan
Động cơ kiểm soát tuân thủ (Compliance Engine) tự động phân tích và phát hiện các rủi ro pháp lý và vận hành trong doanh nghiệp. Hệ thống tự động quét và kiểm tra chéo các dữ liệu về: chấm công vượt giới hạn (OT hours), các giao dịch tài chính không có hóa đơn hợp chuẩn hoặc đối tác không có mã số thuế, và các hợp đồng lao động thiếu thông tin hoặc sai lệch lương.

### Đối tượng (Entities):
- **ComplianceIssue** - Sự cố tuân thủ được phát hiện (loại rủi ro, mức độ nghiêm trọng, mô tả chi tiết, trạng thái xử lý).

---

## 2. Database Schema

### 2.1. Table `t_compliance_issues`
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `employee_id` | BIGINT UNSIGNED | FK → employees.id, NULLABLE | Liên kết nhân sự vi phạm (nếu có) |
| `contract_id` | BIGINT UNSIGNED | FK → contracts.id, NULLABLE | Liên kết hợp đồng vi phạm (nếu có) |
| `transaction_id` | BIGINT UNSIGNED | FK → t_transactions.id, NULLABLE | Liên kết giao dịch tài chính vi phạm (nếu có) |
| `issue_type` | VARCHAR(50) | REQUIRED | Loại vi phạm: OVERTIME_LIMIT (Quá giờ), MISSING_INVOICE (Thiếu HĐ), INVALID_TAX_CODE (Lỗi MST) |
| `severity` | VARCHAR(20) | REQUIRED | Mức độ: HIGH (Nghiêm trọng), MEDIUM (Trung bình), LOW (Thấp) |
| `description` | TEXT | REQUIRED | Mô tả chi tiết hành vi vi phạm |
| `status` | VARCHAR(20) | REQUIRED | Trạng thái: DETECTED (Mới phát hiện), RESOLVED (Đã xử lý), IGNORED (Bỏ qua) |
| `resolved_at` | TIMESTAMP | NULLABLE | Thời điểm xử lý |
| `resolved_by` | BIGINT UNSIGNED | FK → employees.id, NULLABLE | Người phê duyệt xử lý |

---

## 3. API Endpoints

### 3.1. API Kiểm soát Tuân thủ
| Method | URL | Auth | Mô tả |
|--------|-----|------|-------|
| GET | `/api/compliance` | ✅ | Lấy danh sách các vấn đề vi phạm tuân thủ |
| POST | `/api/compliance/scan` | ✅ | Kích hoạt quét tuân thủ thủ công toàn hệ thống |
| PUT | `/api/compliance/{id}/resolve` | ✅ | Xác nhận đã xử lý giải quyết sự cố tuân thủ |

---

## 4. Chi tiết API cốt lõi

### 4.1. GET /api/compliance - Danh sách vi phạm
*   **Query Parameters:**
```
page=1
per_page=15
severity=HIGH|MEDIUM|LOW
status=DETECTED|RESOLVED
```
*   **Response (200):**
```json
{
  "code": 200,
  "data": {
    "items": [
      {
        "id": 4,
        "issue_type": "OVERTIME_LIMIT",
        "severity": "HIGH",
        "description": "Nhân viên Nguyễn Văn A làm thêm 42.5 giờ trong tháng 5/2026, vượt giới hạn 36 Agreement (40 giờ/tháng).",
        "status": "DETECTED",
        "created_at": "2026-06-01 08:00:00"
      }
    ],
    "pagination": {
      "total": 3,
      "per_page": 15,
      "current_page": 1
    }
  }
}
```

### 4.2. POST /api/compliance/scan - Quét hệ thống
*   **Response (200):**
```json
{
  "code": 200,
  "data": {
    "message": "Quá trình quét kiểm soát tuân thủ hoàn tất.",
    "issues_detected_count": 2
  }
}
```
*   **Logic xử lý quét (Business Logic):**
    1. Kiểm tra bảng chấm công trong tháng của từng nhân viên xem có ai vượt quá số giờ quy định của hợp đồng hoặc luật lao động hay không.
    2. Kiểm tra các giao dịch loại chi phí lớn hơn 2.000.000 VNĐ xem có đính kèm hóa đơn hợp lệ và mã đăng ký hóa đơn hay không.
    3. Phát hiện lỗi và tự động chèn bản ghi mới vào bảng `t_compliance_issues` với trạng thái `DETECTED`.

---

## 5. Mối liên hệ với Cấu hình Công ty (Company Settings Integration)
*   **Quét Tính đầy đủ Pháp lý (Legal Completeness Scan):** Động cơ tuân thủ thực hiện đối soát các trường thông tin bắt buộc trong cấu hình công ty (`CompanySetting` như `tax_code`, `corporate_number`, `address_registered`, `legal_representative`). Nếu các thông tin này bị bỏ trống hoặc không hợp lệ, hệ thống sẽ phát hiện và tự động tạo một sự cố tuân thủ mức độ `MEDIUM` nhằm nhắc nhở ban quản trị hoàn thiện hồ sơ doanh nghiệp.
*   **Quét Ký Hợp đồng (Contract Execution Verification):** Hệ thống quét đối chiếu sự tồn tại của con dấu công ty (`hanko_seal_path`) trong cấu hình công ty. Nếu một hợp đồng lao động đã ký có hiệu lực nhưng con dấu doanh nghiệp chưa được đăng ký trong cấu hình, hệ thống sẽ tạo cảnh báo tuân thủ.

