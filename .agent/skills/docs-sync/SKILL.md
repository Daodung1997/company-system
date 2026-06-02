---
name: docs-sync
description: Sync API documentation (functional_spec, entity_reference) with actual code implementation to ensure accuracy for FE team.
---

# Docs Sync Skill

> **Goal**: Đảm bảo tài liệu API trong `docs/features/` luôn khớp 100% với code thực tế, để FE team có thể dựa vào làm mà không gặp sai lệch.

---

## 0. ⚠️ Scope (QUAN TRỌNG)

Skill này **CHỈ** sync các phần sau:
- ✅ **Response format** (JSON structure, fields, HTTP codes)
- ✅ **Validation rules** (Request Payload, Business Rules & Validation)
- ✅ **Error cases** (Validation errors, Business errors)
- ✅ **API Scope table** (Method, Endpoint, Permission)

Skill này **KHÔNG thay đổi**:
- ❌ User Story
- ❌ UI Trigger
- ❌ Flow (Main Flow, Alternative/Error Flow)
- ❌ Acceptance Criteria (GWT)
- ❌ Assumptions / Open Questions
- ❌ Business logic descriptions (chỉ update error codes nếu sai)

---

## 1. 📥 Input

Khi được gọi, xác định **scope** cần sync:
- **Module cụ thể** (vd: `admin-user-management`, `admin-finance`)
- Hoặc **toàn bộ** `docs/features/` nếu user yêu cầu full scan.

---

## 2. 🔍 Research Phase (Đọc Code Thực Tế)

Với mỗi module, đọc theo thứ tự:

### 2.1. Routes
- File: `src/routes/api/{guard}/{module}.php`
- Thu thập: **Method (GET/POST/PUT/PATCH/DELETE)**, **URL path**, **Controller method**, **Middleware/Permission**.

### 2.2. Controller
- File: `src/app/Http/Controllers/Api/{Guard}/{Entity}Controller.php`
- Thu thập: **Method signature**, **Request class**, **Resource class**, **Response format** (`Response::success`, `Response::created`, `Response::pagination`).

### 2.3. FormRequest (Validation)
- File: `src/app/Http/Requests/{Guard}/{Entity}/Store{Entity}Request.php`, `Update{Entity}Request.php`, etc.
- Thu thập: **Tất cả validation rules** (field name, type, required/optional, exists checks, enum values).
- **Đặc biệt chú ý**: Kiểu dữ liệu (string vs integer), tên table trong `exists` rules.

### 2.4. Resource (Response Format)
- File: `src/app/Http/Resources/{Guard}/{Entity}Resource.php`
- Thu thập: **Danh sách fields** trả về, **nested resources**, **conditional loaded relations**.
- Kiểm tra ResourceConst nếu có: `src/app/Constants/Master/Resource/{Entity}ResourceConst.php`

### 2.5. Service (Business Logic)
- File: `src/app/Services/{Guard}/{Entity}Service.php`
- Thu thập: **Side effects** (vd: status thay đổi, notification gửi), **Error cases** (BusinessException codes, HTTP status codes).
- Kiểm tra: Password có được hash không? Có unset field nào không? Transaction wrapping?

### 2.6. Constants
- File: `src/app/Constants/Commons/CommonStatusConst.php` và các Const files liên quan.
- Thu thập: **Giá trị thực tế** của các enum/status (integer vs string, giá trị cụ thể).

---

## 3. 📝 Compare & Identify Discrepancies

Tạo bảng so sánh cho mỗi API endpoint:

| Hạng mục | Docs hiện tại | Code thực tế | Sai lệch? |
|----------|--------------|--------------|-----------|
| HTTP Method | ? | ? | ✅/❌ |
| Route Path | ? | ? | ✅/❌ |
| Request Fields (name, type, required) | ? | ? | ✅/❌ |
| Status/Enum values (string vs int) | ? | ? | ✅/❌ |
| Response structure | ? | ? | ✅/❌ |
| Response fields | ? | ? | ✅/❌ |
| Error cases & HTTP codes | ? | ? | ✅/❌ |
| Permission middleware | ? | ? | ✅/❌ |

### Common Discrepancy Patterns (từ kinh nghiệm):
1. **Status type mismatch**: Docs ghi string (`active`/`inactive`), code dùng integer (`1`/`2`).
2. **Field name mismatch**: Docs ghi `role` (string), code expects `role_ids` (array of IDs).
3. **Route path/method mismatch**: Docs ghi `PATCH /status`, code là `POST /toggle-status`.
4. **Missing fields**: Docs thiếu fields mà code yêu cầu (vd: `avatar_url`, `status` khi create).
5. **Password behavior**: Docs nói update password được, code thực tế `unset` password.
6. **Response wrapper**: Docs wrap `{success, data}` hoặc `{status, data}` → phải dùng `{code, data}`.
7. **Table name prefix**: Docs reference `t_` table, code dùng `m_` table (hoặc ngược lại).

---

## 4. ✍️ Update Documentation

### 4.1. `functional_spec.md`
Cập nhật theo template chuẩn (chỉ các section trong scope):

```markdown
# Functional Spec: [Tên Module]

## 1. User Story
[KHÔNG THAY ĐỔI - giữ nguyên]

---

## 2. UI Trigger
[KHÔNG THAY ĐỔI - giữ nguyên]

---

## 3. Business Rules & Validation

### Validation Rules (422)
> Extracted from FormRequest rules(). FE map theo Message Key.

| Rule ID | Field | Rule | Message Key |
|---------|-------|------|-------------|
| VAL-01 | name | Required | `name.required` |
| VAL-02 | name | Max 100 chars | `name.max` |
| VAL-03 | email | Required | `email.required` |
| VAL-04 | email | Must be valid email | `email.email` |
| VAL-05 | email | Max 100 chars | `email.max` |
| VAL-06 | email | Must be unique | `email.unique` |
| VAL-07 | password | Required | `password.required` |
| VAL-08 | password | Min 8 chars | `password.min` |

### Business Rules (4xx)
> Business logic errors from Service layer. FE map theo error_code.

| Rule ID | Rule | HTTP | error_code |
|---------|------|------|------------|
| BR-01 | Admin cannot deactivate self | 400 | `cannot_deactivate_self` |
| BR-02 | Account must be active | 403 | `AUTH_003` |

---

## 4. Flow
[KHÔNG THAY ĐỔI - giữ nguyên]

---

## 5. Data Mapping
[Chính xác kiểu dữ liệu]
| API Input Field | DB Column | Notes |
|-----------------|-----------|-------|

---

## 6. API Specification

### Endpoint
`METHOD /api/{guard}/{resource}`

### Headers
| Header | Value | Required |
|--------|-------|----------|
| Content-Type | application/json | Yes |
| Accept | application/json | Yes |
| Authorization | Bearer {token} | Yes (trừ auth APIs) |

### Request Payload / Query Parameters
| Field | Type | Required | Validation |
|-------|------|----------|------------|
[Chính xác từ FormRequest rules()]

### Request Example
```json
{ /* example */ }
```

### Response - Success (CODE)
```json
{"code": CODE, "data": { /* from Resource class */ }}
```
> CODE = 201 cho store/create, 200 cho update/get/delete/list

### Response - Pagination (200)
```json
{
  "code": 200,
  "data": {
    "data": [ /* Array of Resource */ ],
    "total": 100,
    "current_page": 1,
    "limit": 20,
    "metadata": {}
  }
}
```

### Response - Validation Error (422)
```json
{"code": 422, "messages": ["field.rule"]}
```

### Possible Validation Messages
| Field | Rule | Message Key |
|-------|------|-------------|
[Extract từ FormRequest rules() → field.rule_name]

### Response - Business Error (4xx)
```json
{"code": STATUS, "messages": {"message": "...", "error_code": "CODE"}}
```
| Case | HTTP | error_code |
|------|------|------------|
[Từ Service BusinessException/catch blocks]

---

## 7. Response Resource Fields
| Field | Type | Mô tả |
|-------|------|-------|
[Từ ResourceConst hoặc Resource toArray()]

---

## 8. Acceptance Criteria (GWT)
[KHÔNG THAY ĐỔI - giữ nguyên]

---

## 9. Assumptions / Open Questions
[KHÔNG THAY ĐỔI - giữ nguyên]
```

### 4.2. `entity_reference.md`
- Cập nhật **Field Mapping** section khớp với code.
- Đảm bảo kiểu dữ liệu status, role, v.v. chính xác.

---

## 5. 📐 Response Standard Reference

> **SOURCE OF TRUTH**: `src/app/Supports/Components/Response/ResponseFormat.php`

### 5.1. Success (200 OK)
Used by: `Response::success($data)`
```json
{
  "code": 200,
  "data": { /* Resource fields */ }
}
```

### 5.2. Created (201)
Used by: `Response::created($data)`
```json
{
  "code": 201,
  "data": { /* Resource fields */ }
}
```

### 5.3. Pagination (200)
Used by: `Response::pagination($collection, $total, $page, $limit)`
```json
{
  "code": 200,
  "data": {
    "data": [ /* Array of Resource */ ],
    "total": 100,
    "current_page": 1,
    "limit": 20,
    "metadata": {}
  }
}
```

### 5.4. Validation Error (422)
Triggered by: `ValidationException` → caught in `Handler.php`
Message format: `RequestTrait.renderMessageFromRule()` → `field.rule`
```json
{
  "code": 422,
  "messages": ["field.rule"]
}
```

**How to extract validation messages from Request class:**
1. Read `rules()` method
2. For each `field => [rule1, rule2, ...]`, generate: `field.rule1`, `field.rule2`, ...
3. Rule name mapping: `required`, `string`, `email`, `max`, `min`, `unique`, `exists`, `confirmed`, `regex`, `in`, `boolean`, `numeric`, `date`

**Example** (`RegisterRequest`):
| Field | Rules | Possible Error Messages |
|-------|-------|------------------------|
| `name` | required, string, max:100 | `name.required`, `name.string`, `name.max` |
| `email` | required, email, unique | `email.required`, `email.email`, `email.unique` |
| `password` | required, min:8, confirmed, regex | `password.required`, `password.min`, `password.confirmed`, `password.regex` |

### 5.5. Business Error (4xx)
Triggered by: `throw new BusinessException(ExceptionCode::CODE, 'message', statusCode)`
```json
{
  "code": 400,
  "messages": {
    "message": "Human-readable error description",
    "error_code": "EXCEPTION_CODE_CONSTANT"
  }
}
```

### 5.6. Generic Error (catch Exception)
Used by: `Response::failure(['message'], statusCode)`
```json
{
  "code": 500,
  "messages": ["Error message string"]
}
```

### 5.7. Not Found (404)
Used by: `Response::notFound($message)`
```json
{
  "code": 404,
  "messages": ["Resource not found"]
}
```

### 5.8. Resource Conflict (409)
Used by: `Response::resourceConflict($data, $actions, 409)`
```json
{
  "code": 409,
  "messages": { /* conflict details */ },
  "resource_conflict_actions": [ /* available actions */ ]
}
```

---

## 6. ✅ Verification Checklist

Sau khi update, kiểm tra lại:

- [ ] Mỗi route trong routes file đều có entry tương ứng trong docs.
- [ ] Mỗi validation rule trong FormRequest khớp với bảng Request Payload.
- [ ] **Business Rules & Validation** section có đầy đủ validation rules từ FormRequest.
- [ ] Validation messages dùng format `field.rule` (vd: `name.required`, `email.email`).
- [ ] Business errors dùng `error_code` từ `BusinessException` trong Service.
- [ ] Mỗi field trong ResourceConst/Resource khớp với Response example.
- [ ] Status values (int/string) nhất quán giữa docs, Constants, và FormRequest.
- [ ] Tên table trong `exists` validation khớp với migration.
- [ ] **Response JSON format** dùng `{code, data}` KHÔNG dùng `{success, data}` hay `{status, data}`.
- [ ] **Validation error** dùng format `field.rule`, KHÔNG dùng custom error codes cho validation.
- [ ] **Success HTTP code** khớp: store → 201, update/get/delete → 200.
- [ ] **Scope check**: Chỉ thay đổi response/validation/error sections, KHÔNG sửa User Story, Flow, GWT.

---

## 7. 📋 Output Format

Khi hoàn thành, báo cáo cho user:

```markdown
## Docs Sync Report: [Module Name]

### Discrepancies Found: X items
| # | Docs cũ ❌ | Code thực tế ✅ |
|---|-----------|----------------|
| 1 | ... | ... |

### Files Updated:
- `docs/features/{module}/01_apis/{entity}/functional_spec.md`
- `docs/features/{module}/03_entity_reference.md`
```
