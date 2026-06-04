# MODULE 7: QUẢN LÝ TÀI LIỆU & HỒ SƠ LƯU TRỮ (DOCUMENT ARCHIVE)
## OpenSpec - Đặc tả chức năng chi tiết

---

## 1. Tổng quan
Module quản lý tài liệu lưu trữ (Electronic Document Management - EDM) cung cấp kho lưu trữ số hóa tập trung cho toàn bộ hệ thống. Các tài liệu được tải lên có thể liên kết trực tiếp với các hồ sơ cụ thể như: Nhân sự (CCCD, hộ chiếu), Hợp đồng, Giao dịch tài chính (Hóa đơn). Hệ thống hỗ trợ xem trước (preview) trực tuyến và tải xuống tài liệu gốc an toàn.

### Đối tượng (Entities):
- **Document** - Bản ghi quản lý siêu dữ liệu của tệp tin tải lên (tên gốc, đường dẫn lưu trữ, loại tệp, dung lượng, quan hệ liên kết).

---

## 2. Database Schema

### 2.1. Table `t_documents`
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `code` | VARCHAR(20) | UNIQUE, REQUIRED | Mã tài liệu tự sinh (DOC-xxxxx) |
| `origin_name` | VARCHAR(255) | REQUIRED | Tên tệp tin gốc của người dùng |
| `file_path` | VARCHAR(255) | REQUIRED | Đường dẫn lưu trữ vật lý trên disk |
| `disk` | VARCHAR(50) | DEFAULT 'public' | Driver lưu trữ (local, s3...) |
| `extension` | VARCHAR(10) | REQUIRED | Đuôi mở rộng tệp tin: pdf, png, jpg, docx |
| `filesize` | BIGINT | REQUIRED | Dung lượng tệp tin (byte) |
| `documentable_id` | BIGINT UNSIGNED | NULLABLE | ID của thực thể được liên kết |
| `documentable_type` | VARCHAR(255) | NULLABLE | Class của thực thể được liên kết (Morph) |
| `employee_id` | BIGINT UNSIGNED | FK → employees.id, NULLABLE | Liên kết nhân sự |
| `contract_id` | BIGINT UNSIGNED | FK → contracts.id, NULLABLE | Liên kết hợp đồng |
| `transaction_id` | BIGINT UNSIGNED | FK → t_transactions.id, NULLABLE | Liên kết giao dịch tài chính |
| `status` | VARCHAR(20) | REQUIRED | Trạng thái hoạt động (ACTIVE, DELETED) |

---

## 3. API Endpoints

### 3.1. API Lưu trữ tài liệu (EDM)
| Method | URL | Auth | Mô tả |
|--------|-----|------|-------|
| GET | `/api/documents` | ✅ | Lấy danh sách tài liệu lưu trữ |
| POST | `/api/documents/upload` | ✅ | Tải tệp tin lên hệ thống (lưu tạm hoặc lưu chính thức) |
| POST | `/api/documents/attach` | ✅ | Thiết lập liên kết tài liệu với Hồ sơ/Hợp đồng/Giao dịch |
| DELETE | `/api/documents/{id}` | ✅ | Xóa tài liệu khỏi hệ thống |
| GET | `/api/documents/{id}/download` | ✅ | Tải xuống tệp tin gốc |

---

## 4. Chi tiết API cốt lõi

### 4.1. POST /api/documents/upload - Tải tệp tin lên
*   **Request Form-Data:**
    *   `file`: Tệp tin tải lên (yêu cầu kiểm tra kích thước không quá 10MB và định dạng được hỗ trợ: pdf, png, jpg, docx, xlsx).
    *   `category`: Chuỗi phân loại thư mục lưu trữ (ví dụ: `contracts`, `employees`, `transactions`).
*   **Response (200):**
```json
{
  "code": 200,
  "data": {
    "id": 45,
    "code": "DOC-00000000000000000045",
    "origin_name": "hop_dong_lao_dong_scan.pdf",
    "file_path": "documents/1780563697_8cHoUeknBcBEy8Fs.pdf",
    "extension": "pdf",
    "filesize": 1548230,
    "url": "http://localhost/storage/documents/1780563697_8cHoUeknBcBEy8Fs.pdf"
  }
}
```
*   **Lưu ý:** Tệp tin tải lên sẽ được lưu vào đĩa cứng và trả về một đường dẫn truy cập công khai/nội bộ hoặc URL xem trước trực tiếp.

---

## 5. Mối liên hệ với Cấu hình Công ty (Company Settings Integration)
*   **Tải ảnh Logo & Ảnh nền (Logo & Background Upload):** Khi cấu hình logo (`logo_path`) hoặc hình nền đăng nhập (`background_path`) tại trang Cấu hình Công ty, hệ thống sẽ sử dụng API Upload Tài liệu `/api/documents/upload` với thư mục `category` là `company_settings` để thực hiện đẩy ảnh lên máy chủ, nhận về thông tin lưu trữ trước khi lưu vào bảng `company_settings`.
*   **Quản lý Con dấu (Hanko Seal File Management):** Tệp tin con dấu công ty (`hanko_seal_path`) được lưu trữ và kiểm soát như một tài nguyên tài liệu thông thường, được tải lên thông qua cơ chế EDM của hệ thống để đảm bảo tính an toàn.

