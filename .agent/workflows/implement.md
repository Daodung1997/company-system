---
description: 🏗️ Implement Service/Feature theo Strict Spec + Project Conventions
---

# WORKFLOW: /implement

> **Trigger**: Khi User gõ `/implement [module name]` hoặc các cụm từ:
> - "implement module [module]"
> - "code feature [feature]"
> - "viết code cho [module/feature]"
> - "thực hiện chức năng [feature]"

**Mục tiêu**: Kích hoạt skill `feature-implementer` để biến document (spec) thành production code chất lượng cao, có test coverage và tự sửa lỗi.

---

## 1. Context Loading

**Action**: Đọc nguồn sự thật (Source of Truth).
1. Đọc: `.agent/skills/feature-implementer/SKILL.md` (Để load quy trình chuẩn)
2. Đọc: `docs/app/01_module_map.md` (Để tìm thư mục của module)
3. Tìm docs của module:
   ```bash
   ls -R docs/features | grep [module_name_tương_đối]
   ```

## 2. Planning

**Action**: Tạo `implementation_plan.md` (nếu chưa có hoặc User yêu cầu).
- Xác định API list từ `docs/features/{module}/01_apis/`
- Mapping DB Tables từ `docs/app/database_schema.md`
- List các file sẽ tạo/sửa:
  1. Constants (`App/Constants/...`)
  2. Model (`App/Models/...`)
  3. Migration (nếu cần)
  4. Repository & Criteria (`App/Repositories/...`)
  5. Service (`App/Services/...`)
  6. Request (`App/Http/Requests/...`)
  7. Resource (`App/Http/Resources/...`)
  8. Controller (`App/Http/Controllers/...`)
  9. Route (`routes/api/...`)
  10. Test (`tests/Feature/...`)

## 3. Strict Implementation Loop

**Action**: Thực hiện tuần tự theo quy trình:

### 3.1. Infrastructure Layer
- Tạo Constants trước (Tránh magic strings).
- Tạo/Update Migration & Model.
- Tạo Repository & Binding.
- **Quan trọng**: Tạo `SortAndFilter{Entity}Criteria` nếu có API list/search.

### 3.2. Service Layer
- Viết Business Logic trong Service.
- **Bắt buộc**: Wrap write operations trong `DB::beginTransaction()`.
- **Bắt buộc**: Dùng `Criteria` cho list methods.
- **Bắt buộc**: Throw `BusinessException` (kèm `ExceptionCode`) cho lỗi logic, không return array lỗi.

### 3.3. HTTP Layer
- Tạo Request Validation (Dùng `BaseRequest`).
- Tạo Resource (Dùng `Response::success` facades).
- Tạo Controller (Chỉ gọi Service, không logic).

### 3.4. Routing
- Đăng ký route trong `routes/api.php` hoặc file phù hợp.
- Apply middleware group `['auth:admin']` hoặc `['auth:api']` đúng context.

## 4. Verification & Testing

**Action**: Chạy test và tự sửa (Self-Healing).

1. **Tạo Test**:
   - File: `tests/Feature/{Module}/{Entity}Test.php`
   - Case: Success (200), Validation (422), Auth (401/403), Logic (400).

2. **Chạy Test**:
   ```bash
   php artisan test --filter {TestClassName}
   ```

3. **Debug Loop (Nếu fail)**:
   - Đọc lỗi -> Sửa code -> Chạy lại test.
   - **Tuyệt đối không** comment out test để pass.
   - Sửa đến khi GREEN.

## 5. Finalize

- **Update Task**: Cập nhật `task.md`.
- **Notify**: Báo cáo User kết quả + danh sách file đã tạo.
