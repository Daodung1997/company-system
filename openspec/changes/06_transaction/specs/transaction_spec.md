# MODULE 6: QUẢN LÝ THU CHI & CHI PHÍ (CASH FLOW & EXPENSES)
## OpenSpec - Đặc tả chức năng chi tiết

---

## 1. Tổng quan
Module quản lý thu chi và chi phí hỗ trợ doanh nghiệp SaaS ghi nhận các hoạt động giao dịch tài chính (thu từ khách hàng, chi trả lương, thanh toán hóa đơn đối tác). Hệ thống tự động tách biệt phần thuế giá trị gia tăng (VAT), khấu trừ thuế thu nhập cá nhân/đối tác (withholding tax) và hỗ trợ lưu trữ số hóa hóa đơn chứng từ đính kèm (Qualified Invoices).

### Đối tượng (Entities):
- **Transaction** - Giao dịch tài chính (mã, số tiền, loại thuế suất, phương thức thanh toán, danh mục chi tiêu, ngày giao dịch).
- **Document** - Hóa đơn chứng từ đính kèm với giao dịch (Qualified Invoice PDF hoặc ảnh).

---

## 2. Database Schema

### 2.1. Table `t_transactions`
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `code` | VARCHAR(20) | UNIQUE, REQUIRED | Mã giao dịch tự động sinh (TXN-xxxxx) |
| `type` | VARCHAR(20) | REQUIRED | Loại giao dịch: INCOME (Thu), EXPENSE (Chi) |
| `amount` | DECIMAL(15,2) | REQUIRED | Tổng số tiền giao dịch (đã gồm thuế) |
| `net_amount` | DECIMAL(15,2) | REQUIRED | Số tiền trước thuế |
| `tax_amount` | DECIMAL(15,2) | REQUIRED | Tiền thuế |
| `tax_rate_type` | VARCHAR(20) | REQUIRED | Loại thuế suất: VAT_10, VAT_8, VAT_0, EXEMPT (Miễn thuế) |
| `invoice_registration_number` | VARCHAR(50) | NULLABLE | Mã đăng ký hóa đơn hợp chuẩn (JP Invoice System) |
| `withholding_tax` | DECIMAL(15,2) | DEFAULT 0.00 | Thuế khấu trừ tại nguồn |
| `payment_method` | VARCHAR(30) | REQUIRED | Phương thức: BANK_TRANSFER, CASH, CREDIT_CARD |
| `category` | VARCHAR(50) | REQUIRED | Danh mục: SALARY, OUTSOURCING, RENT, OFFICE, UTILITIES, SALES |
| `transaction_date` | DATE | REQUIRED | Ngày phát sinh giao dịch |
| `description` | TEXT | NULLABLE | Mô tả chi tiết giao dịch |
| `status` | VARCHAR(20) | REQUIRED | Trạng thái: DRAFT, COMPLETED, CANCELLED |

---

## 3. API Endpoints

### 3.1. Quản lý Giao dịch (Transactions)
| Method | URL | Auth | Mô tả |
|--------|-----|------|-------|
| GET | `/api/transactions` | ✅ | Lấy danh sách giao dịch thu chi (lọc, phân trang) |
| POST | `/api/transactions` | ✅ | Ghi nhận giao dịch mới |
| GET | `/api/transactions/{id}` | ✅ | Xem chi tiết một giao dịch |
| PUT | `/api/transactions/{id}` | ✅ | Cập nhật thông tin giao dịch |
| DELETE | `/api/transactions/{id}` | ✅ | Xóa giao dịch (soft delete) |

---

## 4. Chi tiết API cốt lõi

### 4.1. GET /api/transactions - Danh sách giao dịch
*   **Query Parameters:**
```
page=1
per_page=15
search=<mã giao dịch hoặc mô tả>
type=INCOME|EXPENSE
category=SALARY|OFFICE
start_date=2026-06-01
end_date=2026-06-30
```
*   **Response (200):**
```json
{
  "code": 200,
  "data": {
    "items": [
      {
        "id": 12,
        "code": "TXN-00000000000000000012",
        "type": "EXPENSE",
        "amount": 2200000.00,
        "net_amount": 2000000.00,
        "tax_amount": 200000.00,
        "tax_rate_type": "VAT_10",
        "payment_method": "BANK_TRANSFER",
        "category": "OFFICE",
        "transaction_date": "2026-06-03",
        "status": "COMPLETED"
      }
    ],
    "pagination": {
      "total": 50,
      "per_page": 15,
      "current_page": 1
    }
  }
}
```

### 4.2. POST /api/transactions - Ghi nhận giao dịch
*   **Request Body Schema:**
```json
{
  "type": "EXPENSE",
  "amount": 2200000.00,
  "net_amount": 2000000.00,
  "tax_amount": 200000.00,
  "tax_rate_type": "VAT_10",
  "invoice_registration_number": "T1234567890123",
  "payment_method": "BANK_TRANSFER",
  "category": "OFFICE",
  "transaction_date": "2026-06-03",
  "description": "Thanh toán tiền văn phòng phẩm tháng 6"
}
```
*   **Response (201):**
```json
{
  "code": 201,
  "data": {
    "id": 12,
    "code": "TXN-00000000000000000012",
    "status": "COMPLETED"
  }
}
```

---

## 5. Mối liên hệ với Cấu hình Công ty (Company Settings Integration)
*   **Hóa đơn Đăng ký Thuế (Tax Registered Invoices):** Giao dịch chi phí lớn có kiểm tra chéo mã số thuế của công ty (`tax_code`) trong cấu hình công ty với hóa đơn Qualified Invoice đầu vào. Hệ thống sử dụng thông tin đăng ký thuế của doanh nghiệp để xác minh tính tuân thủ pháp lý của giao dịch thu chi.
*   **Thống kê & Đối chiếu:** Tên công ty chính thức (`company_name`) từ cấu hình công ty được sử dụng trong các mẫu báo cáo kết xuất dòng tiền, sổ quỹ tiền mặt xuất ra cho quản trị viên và cơ quan Thuế.

