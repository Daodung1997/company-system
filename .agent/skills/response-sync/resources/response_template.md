# Response Section Template for functional_spec.md

> Copy this template vào phần Response của `functional_spec.md` và fill data từ code.

---

## Response - Success ({CODE})

```json
{
  "code": {CODE},
  "data": {
    "{RESOURCE_KEY}": {
      // fields from {Entity}Resource.toArray()
    }
  }
}
```

> `{CODE}` = 201 cho store/create, 200 cho update/get/delete/show

---

## Response - Pagination (200)

```json
{
  "code": 200,
  "data": {
    "data": [
      {
        // fields from {Entity}Resource.toArray()
      }
    ],
    "total": 100,
    "current_page": 1,
    "limit": 20,
    "metadata": {}
  }
}
```

---

## Response - Validation Error (422)

```json
{
  "code": 422,
  "messages": ["{field}.{rule}"]
}
```

**Possible Validation Messages:**

| Field | Rule | Message Key |
|-------|------|-------------|
| `{field}` | required | `{field}.required` |
| `{field}` | email | `{field}.email` |
| `{field}` | unique | `{field}.unique` |
| `{field}` | exists | `{field}.exists` |
| `{field}` | min | `{field}.min` |
| `{field}` | max | `{field}.max` |
| `{field}` | confirmed | `{field}.confirmed` |
| `{field}` | regex | `{field}.regex` |
| `{field}` | in | `{field}.in` |

> **NOTE**: Chỉ message đầu tiên fail được trả về (Handler lấy `errors[0][0]`).

---

## Response - Business Error ({CODE})

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
| {Case description} | {code} | `{ExceptionCode::CONSTANT}` |

---

## Response - Not Found (404)

```json
{
  "code": 404,
  "messages": ["Resource not found"]
}
```

---

## Authentication Errors (if route has auth middleware)

| Case | HTTP | Response |
|------|------|----------|
| Token missing | 401 | `{"code": 401, "messages": ["token.required"]}` |
| Token expired | 401 | `{"code": 401, "messages": ["token.expired"]}` |
| Token invalid | 401 | `{"code": 401, "messages": ["token.invalid"]}` |
