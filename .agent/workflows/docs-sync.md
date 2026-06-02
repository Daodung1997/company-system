---
description: Đồng bộ tài liệu đặc tả API (functional_spec, entity_reference) với mã nguồn thực tế
---

# Quy trình đồng bộ hóa đặc tả (Docs Sync Workflow)

Quy trình này đảm bảo tài liệu đặc tả API trong thư mục `docs/features/` luôn khớp chính xác 100% với mã nguồn Laravel API thực tế, giúp đội ngũ phát triển Frontend (Flutter / Vue3) tích hợp chính xác mà không gặp bất kỳ sai lệch nào.

## Các trường hợp cần chạy Docs Sync:
1. Khi vừa code xong một API mới hoặc vừa chỉnh sửa API hiện tại.
2. Trước khi commit code hoặc đưa gói thay đổi sang giai đoạn review.
3. Khi chuẩn bị lưu trữ gói thay đổi (`/opsx-archive`).

---

## Các bước thực hiện đồng bộ:

### Bước 1: Thu thập thông tin từ mã nguồn thực tế (Research)
Đọc mã nguồn Laravel theo đúng thứ tự ưu tiên:
1. **Routes (`src/routes/api/...`)**: Xác định URL, HTTP Method, Tên Controller/Action, Middleware/Permission.
2. **Controller (`src/app/Http/Controllers/Api/...`)**: Xác định API Request Class, Resource Class và kiểu Response format (`Response::success`, `Response::created`, `Response::pagination`).
3. **Form Request (`src/app/Http/Requests/...`)**: Thu thập đầy đủ các quy tắc validation đầu vào (validation rules) và keys tin nhắn lỗi.
4. **API Resource (`src/app/Http/Resources/...`)**: Thu thập cấu trúc các trường JSON trả về, bao gồm cả các relations loaded.
5. **Service (`src/app/Services/...`)**: Thu thập logic lỗi nghiệp vụ (`BusinessException` codes) để cập nhật vào bảng lỗi nghiệp vụ (Business Rules).

### Bước 2: Tạo hoặc cập nhật tài liệu Stable Specs
1. Đối với mỗi API, tạo mới hoặc cập nhật file đặc tả chức năng stable tại:
   `docs/features/{module}/01_apis/{api_name}/functional_spec.md`
2. Đảm bảo cấu trúc tài liệu tuân thủ định dạng chuẩn của dự án:
   - **Validation Rules**: Liệt kê các rule validation kèm tin nhắn lỗi dạng `field.rule` (ví dụ: `code.required`, `discount_value.numeric`).
   - **Business Rules**: Các lỗi logic ném ra kèm `error_code` chuẩn của dự án (ví dụ: `VOUCHER_EXPIRED`).
   - **API Specification**: Đặc tả chính xác HTTP Method, Endpoint, Query Params / Payload mẫu, Response Success (dạng `{code: 200, data: {...}}` hoặc Pagination), Response Validation Error (422), Response Business Error (4xx).
   - **Response Resource Fields**: Định nghĩa chi tiết kiểu dữ liệu và mô tả của từng trường trả về.

### Bước 3: Đồng bộ sơ đồ Entity Reference
Cập nhật file tham chiếu thực thể stable tại:
`docs/features/{module}/03_entity_reference.md`
Đảm bảo định nghĩa rõ ràng kiểu dữ liệu cột trong database và mapping với payload của API.

---

## Chuẩn Response bắt buộc tuân thủ:

1. **Success (200 OK)**:
   ```json
   {
     "code": 200,
     "data": { /* Resource fields */ }
   }
   ```
2. **Created (201 Created)**:
   ```json
   {
     "code": 201,
     "data": { /* Resource fields */ }
   }
   ```
3. **Pagination (200 OK)**:
   ```json
   {
     "code": 200,
     "data": {
       "data": [ /* Array of Resource */ ],
       "total": 100,
       "current_page": 1,
       "limit": 15,
       "metadata": {}
     }
   }
   ```
4. **Validation Error (422)**:
   ```json
   {
     "code": 422,
     "messages": ["field.rule"]
   }
   ```
5. **Business Error (4xx)**:
   ```json
   {
     "code": 400,
     "messages": {
       "message": "Human-readable error description",
       "error_code": "EXCEPTION_CODE_CONSTANT"
     }
   }
   ```
