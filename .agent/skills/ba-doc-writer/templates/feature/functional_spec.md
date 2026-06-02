# <api_name> - Functional Specification

## 1. User Story

**As a** <actor>,
**I want to** <action>,
**So that** <benefit>.

---

## 2. UI Trigger

- **Screen**: <screen name or N/A>
- **Action**: <trigger action: Button / Cron / Webhook / System Event>

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
| VAL-05 | email | Must be unique | `email.unique` |

> **Message Key format**: `field.rule_name` (vd: `email.required`, `password.min`, `name.max`)
> Rule name mapping: `required`, `string`, `email`, `max`, `min`, `unique`, `exists`, `confirmed`, `regex`, `in`, `boolean`, `numeric`, `date`, `size`

### Business Rules (4xx)
> Business logic errors from Service layer. FE map theo error_code.

| Rule ID | Rule | HTTP | error_code |
|---------|------|------|------------|
| BR-01 | Account must be active | 403 | `AUTH_003` |
| BR-02 | Cannot deactivate self | 400 | `cannot_deactivate_self` |

- **Permissions**: [Role/condition...]
- **Idempotency / Concurrency** (nếu có): ...

---

## 4. Flow

### Main Flow

```
1. Step 1
2. Step 2
...
```

### Alternative/Error Flow

| Condition | Flow |
|-----------|------|
| Validation fails | Return 422 with field.rule messages |
| Business error | Return 4xx with error_code |

---

## 5. Data Mapping

| API Input Field | DB Column (Table.Column) | Notes |
|-----------------|--------------------------|-------|
| field_name | table.column | type, constraints |

---

## 6. API Specification

### Endpoint

`METHOD /api/<guard>/<resource>`

### Headers

| Header | Value | Required |
|--------|-------|----------|
| Content-Type | application/json | Yes |
| Accept | application/json | Yes |
| Authorization | Bearer {token} | Yes (if authenticated) |

### Request Payload / Query Parameters

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| field | type | ✅/No | rules from FormRequest |

### Request Example

```json
{ /* example payload */ }
```

### Response - Success (CODE)

> CODE = 201 cho store/create, 200 cho update/get/delete/list

```json
{
  "code": CODE,
  "data": { /* from Resource class */ }
}
```

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
{
  "code": 422,
  "messages": ["field.rule"]
}
```

### Response - Business Error (4xx)

```json
{
  "code": STATUS,
  "messages": {
    "message": "Human-readable error description",
    "error_code": "EXCEPTION_CODE_CONSTANT"
  }
}
```

| Case | HTTP | error_code |
|------|------|------------|
| Description | 4xx | `ERROR_CODE` |

---

## 7. Response Resource Fields

| Field | Type | Mô tả |
|-------|------|-------|
| id | int/uuid | Primary Key |

---

## 8. Acceptance Criteria (GWT)

### AC-01: <Happy Path>

```gherkin
Given <precondition>
When <action>
Then <expected result>
```

### AC-02: <Error Case>

```gherkin
Given <precondition>
When <action>
Then <expected result>
```

---

## 9. Assumptions / Open Questions

- [ASSUMPTION] ...
- [OPEN] ...
