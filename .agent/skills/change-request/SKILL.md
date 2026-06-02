---
name: change-request
description: Handles ad-hoc user requirements or changes to existing features by enforcing a "Docs-First" approach before writing any code or tests.
---

# Implement Change Request Skill

> **Goal**: Khi User yêu cầu thêm một field mới, sửa logic, hoặc thay đổi requirement của một tính năng ĐÃ CÓ, tuyệt đối không code ngay. Phải cập nhật Tài Liệu (Docs) trước, sau đó mới dùng Docs để dẫn dắt Code và Test.

---

## 1. 🛑 STOP & Phân Tích (No Code)

Khi nhận được yêu cầu thay đổi (VD: "Thêm cột lý do khi khoá user"):
1. Đặt câu hỏi: Change này ảnh hưởng đến API nào? Frontend cần hứng data này ở đâu? Database cần lưu kiểu gì?
2. Tìm các file Docs liên quan trong `docs/features/{module}/`.
3. Tìm file schema database `docs/app/database_schema.md`.

---

## 2. 📝 Bước 1: UPDATE DOCS FIRST (Bắt buộc)

**HÀNH ĐỘNG**: Sửa các file Markdown tài liệu TRƯỚC TIÊN.

### 2.1 Cập nhật DB Schema
- Mở `docs/app/database_schema.md` (hoặc file tương đương).
- Thêm định nghĩa cột mới (Tên, Kiểu dữ liệu, Nullable hay không).

### 2.2 Cập nhật Functional Specs
- Mở `docs/features/{module}/01_apis/{api}/functional_spec.md`.
- Sửa payload **Request Body** (nếu yêu cầu Frontend truyền thêm data).
- Sửa payload **Response Body** (nếu yêu cầu trả thêm data về cho Frontend).
- Sửa **Business Rules** (nếu logic thay đổi).

### 2.3 Cập nhật OpenAPI
- Mở `docs/api/openapi.yaml`.
- Thêm field tương ứng vào `components/schemas`.

---

## 3. 💻 Bước 2: CODE IMPLEMENTATION

Sau khi Specs đã update, tiến hành code bám sát 100% theo Docs bằng cách áp dụng skill `feature-implementer`:

1. **Migration**: Tạo migration thêm cột dựa đúng theo DB Schema file vừa sửa. (Thêm vào `$fillable` model).
2. **Request Validation**: Thêm rule validate vào FormRequest tương ứng.
3. **Service Logic**: Sửa code logic xử lý field này.
4. **Resource Mapping**: Đưa field này ra API Response Output qua API Resource.

---

## 4. 🧪 Bước 3: ĐẢM BẢO TEST CHUẨN XÁC (Tránh False Positive)

Khi specs thay đổi thêm trường mới, phải update Unit Tests để **bắt chặt** sự thay đổi này (Dựa theo Rule 5.5 & 5.6 của `feature-implementer`):

1. **Test Query Params**: Nếu sửa API GET, phải mô phỏng lại đúng cấu trúc lồng array của URL param (VD: `filters[status]=...&search[name]=...`).
2. **Strict Side-Effect Assertion**: Nếu API Request gửi lên `{ "status": 2, "reason": "Vi phạm" }`, hàm `assertDatabaseHas()` bắt buộc phải soi CẢ HAI CỘT `status` HOẶC `reason`. Tuyệt đối không được bỏ sót.

---

## 5. 🔄 Bước 4: VERIFY BẰNG DOCS-SYNC
Sau khi code và test xanh (Passed), sử dụng skill `docs-sync` quét qua để đảm bảo Code vừa viết không làm gãy sự đồng bộ với các phần khác của Specs. Báo cáo bằng bảng `Discrepancies Found` cho User để chốt sổ.
