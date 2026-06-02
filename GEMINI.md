---
trigger: always_on
---

# Project: viec-vat-ai-be (Laravel REST API)

> **This file is auto-read by Gemini CLI.** It ensures ALL agents (including headless batch runs) follow project rules.

---

## MANDATORY RULES (Read BEFORE any code)

You MUST read and strictly follow these files in order:

1. **`.agent/rules/coding-conventions.md`** — Code patterns, Response facade, Repository, Service, Request, Resource, Constants, Security (IDOR, Data Leakage).
2. **`.agent/rules/laravel-common-cores.md`** — Reusable components: BaseModel, BaseMasterModel, AbstractService, RequestTrait, Response facade, Constants system, Repository pattern.
3. **`.agent/rules/definition-of-done.md`** — Quality gates: >80% test coverage, linter pass, acceptance criteria.

---

## Architecture (Non-negotiable)

```
Controller (thin) → Service (extends AbstractService) → Repository (Criteria pattern)
```

- **Controller**: Only calls Service, returns `Response::success/created/failure`.
- **Service**: Business logic, `beginTransaction()/commitTransaction()/rollbackTransaction()`.
- **Repository**: Data access via Criteria pattern. NEVER use `Model::where()` directly in Service.

---

## Key Patterns Quick Reference

### Response
```php
use App\Supports\Facades\Response\Response;
Response::success($data);        // 200
Response::created($data);       // 201
Response::failure($msg, 400);   // Error
Response::pagination($collection, $total, $page, $limit); // Paginated
```

### Exception Handling
```php
use App\Exceptions\BusinessException;
use App\Constants\Commons\ExceptionCode;
throw new BusinessException(ExceptionCode::CODE, 'message', 404);
// NEVER: throw new Exception() or HttpResponseException
```

### Request Validation
```php
use App\Traits\RequestTrait;
class StoreXRequest extends FormRequest {
    use RequestTrait;
    public function rules(): array { return [...]; }
    public function messages(): array { return $this->renderMessageFromRule($this->rules()); }
}
```

### Model
```php
// Business entity → extends BaseModel
// Master data (auto-code) → extends BaseMasterModel
// NEVER manually set created_by/updated_by (BaseModel handles it)
```

### Constants
```php
// NEVER hardcode statuses/types/roles
use App\Constants\Commons\CommonStatusConst;  // ACTIVE, INACTIVE, DELETED
use App\Constants\Master\Models\{Entity}\{Entity}Column;
```

### Resource Nested Relationships
```php
// ALWAYS use Resource::collection or new Resource for relationships. NEVER map inline array.
'services' => ServiceResource::collection($this->whenLoaded('services')),
```

### Security
```php
// IDOR: Always scope by owner
$this->repository->findWhere(['id' => $id, 'user_id' => auth()->id()]);
// Data Leakage: Always return Resource, NEVER raw Model
return Response::success(new XResource($model));
```

### Service Transaction
```php
$this->beginTransaction();
try {
    $this->repository->create($data);
    $this->commitTransaction();
} catch (\Throwable $e) {
    $this->rollbackTransaction();
    throw $e;
}
```

### Repository Criteria
```php
// List endpoints MUST use SortAndFilter Criteria
$this->repository->pushCriteria(
    new SortAndFilterXCriteria($filters, $sorts, $search)
)->paginate($limit);
```

---

## File Organization
```
Controllers/{Module}/{Entity}Controller.php
Services/{Module}/{Entity}Service.php
Requests/{Module}/{Entity}/{Action}{Entity}Request.php
Resources/{Module}/{Entity}/{Entity}Resource.php
Constants/Master/Models/{Entity}/*.php
Repositories/Criteria/{Module}/{Entity}/SortAndFilter{Entity}Criteria.php
routes/api/{module}.php
tests/Feature/{Module}/{Entity}Test.php
```

---

## Testing Requirements
- Feature Tests MANDATORY for every endpoint
- Cover: Success (200/201), Validation (422), Auth (401/403), Business Logic (400)
- `assertDatabaseHas` MUST check ALL fields sent in request body
- Query params: Use `filters[status]=active` format, NOT flat `?status=active`
