---
name: response-sync
description: Sync API response documentation with actual code. Reads Request validation rules → Controller response types → Resource fields → generates accurate response docs.
---

# Response Sync Skill

> **Goal**: Đọc code thực tế (Request, Controller, Resource, Service) → tự động generate/update phần Response trong `functional_spec.md` cho chính xác 100%.

---

## 1. 📥 Input

Khi được gọi, xác định scope:
- **API cụ thể** (vd: `user-auth/register`, `admin-configuration/update_platform_fees`)
- **Module** (vd: `user-auth` → sync all APIs in module)

---

## 2. 🔍 Step-by-Step Process

### Step 1: Locate Request Class

```
src/app/Http/Requests/{Guard}/{Entity}/{Action}{Entity}Request.php
```

**Extract from `rules()` method:**
- field name
- rule list (required, string, email, max, min, unique, exists, confirmed, regex, in, boolean, numeric, date)

**Generate validation message table:**
For each `field => [rule1, rule2, ...]`:
```
field.rule1, field.rule2, ...
```

> **CRITICAL**: Ignore rules that are TYPE rules (string, integer, array) — they rarely trigger as user-facing errors. Focus on: `required`, `email`, `unique`, `exists`, `min`, `max`, `confirmed`, `regex`, `in`, `boolean`.

### Step 2: Determine Success Response Code

Read Controller method:
- `Response::created(...)` → **201**
- `Response::success(...)` → **200**
- `Response::pagination(...)` → **200** (pagination format)

**Mapping convention:**
| Controller Action | HTTP Method | Response Type | Code |
|-------------------|-------------|---------------|------|
| `store` / `create` / `register` | POST | `Response::created()` | 201 |
| `update` / `toggle` | PUT/PATCH | `Response::success()` | 200 |
| `index` / `list` | GET | `Response::pagination()` | 200 |
| `show` / `detail` / `get` | GET | `Response::success()` | 200 |
| `destroy` / `delete` | DELETE | `Response::success()` | 200 |

### Step 3: Extract Resource Fields

Read Resource class:
```
src/app/Http/Resources/{Guard}/{Entity}/{Entity}Resource.php
```

Extract `toArray()` method → list of fields returned.
Check `ResourceConst` if used:
```
src/app/Constants/Master/Resource/{Entity}ResourceConst.php
```

### Step 4: Extract Business Errors

Read Service class + Controller catch blocks:
- `throw new BusinessException(ExceptionCode::CODE, 'message', statusCode)`
- `Response::failure(['message' => ...], statusCode)` in catch blocks
- `Response::notFound(...)` calls

**Collect:**
| Error Case | HTTP Code | error_code | message |
|------------|-----------|------------|---------|

### Step 5: Extract Auth/Common Errors

Check middleware on route:
- `auth:api` → may return 401 (token expired/invalid/required)
- Permission middleware → may return 403

---

## 3. ✍️ Generate Response Documentation

Use this exact template for the Response section in `functional_spec.md`:

### Response - Success ({CODE})

```json
{
  "code": {CODE},
  "data": {
    // fields from Resource class
  }
}
```

### Response - Validation Error (422)

```json
{
  "code": 422,
  "messages": ["{first_failed_field}.{rule}"]
}
```

**Possible Validation Messages:**

| Field | Rule | Message Key |
|-------|------|-------------|
| {field} | {rule} | `{field}.{rule}` |

> **NOTE**: Chỉ validation message **đầu tiên** được trả về (do Handler lấy `errors[0][0]`).

### Response - Business Error ({CODE})

```json
{
  "code": {STATUS_CODE},
  "messages": {
    "message": "{error_description}",
    "error_code": "{EXCEPTION_CODE}"
  }
}
```

| Case | HTTP | error_code |
|------|------|------------|
| {case description} | {code} | `{ExceptionCode::CONSTANT}` |

### For Pagination APIs

```json
{
  "code": 200,
  "data": {
    "data": [
      { /* Resource fields */ }
    ],
    "total": 100,
    "current_page": 1,
    "limit": 20,
    "metadata": {}
  }
}
```

---

## 4. 📐 Response Format Quick Reference

| Response Type | Facade Method | HTTP Code | JSON Structure |
|---------------|---------------|-----------|----------------|
| Success | `Response::success()` | 200 | `{code, data}` |
| Created | `Response::created()` | 201 | `{code, data}` |
| Pagination | `Response::pagination()` | 200 | `{code, data: {data[], total, current_page, limit, metadata}}` |
| Validation Error | Auto (Handler) | 422 | `{code, messages: ["field.rule"]}` |
| Business Error | `BusinessException` | 4xx | `{code, messages: {message, error_code}}` |
| Generic Error | `Response::failure()` | 5xx | `{code, messages: ["message"]}` |
| Not Found | `Response::notFound()` | 404 | `{code, messages: ["message"]}` |
| Conflict | `Response::resourceConflict()` | 409 | `{code, messages, resource_conflict_actions}` |

---

## 5. ✅ Verification After Update

- [ ] Success response JSON dùng `{code, data}`, KHÔNG dùng `{success, data}`
- [ ] Validation messages dùng `field.rule` format, KHÔNG dùng custom error codes (`VAL_001`)
- [ ] HTTP code match: store → 201, khác → 200
- [ ] Tất cả fields trong Request `rules()` có entry trong Validation Messages table
- [ ] Resource fields match với actual Resource class `toArray()`
- [ ] Business errors match `BusinessException` trong Service

---

## 6. 📋 Output

Khi hoàn thành, report:

```markdown
## Response Sync Report: [API Name]

### Changes Made:
| Section | Before | After |
|---------|--------|-------|
| Success response format | `{success: true}` | `{code: 200, data: {...}}` |
| Validation error format | Custom codes | `field.rule` messages |
| ... | ... | ... |

### Validation Messages Generated:
| Field | Possible Messages |
|-------|-------------------|
| email | email.required, email.email, email.unique |
| ... | ... |
```
