# Implement Feature theo Strict Spec

> Trigger: `/implement [module name]`
> Muc tieu: Bien document (spec) thanh production code chat luong cao, co test coverage.

## 1. Context Loading

1. Doc `CLAUDE.md` (rules + conventions)
2. Doc `docs/app/01_module_map.md` (tim thu muc module)
3. Tim docs cua module:
   ```bash
   ls -R docs/features | grep [module_name]
   ```
4. Doc spec files trong `docs/features/{module}/01_apis/`

## 2. Planning

Tao implementation plan:
- Xac dinh API list tu `docs/features/{module}/01_apis/`
- Mapping DB Tables tu `docs/app/database_schema.md`
- List cac file se tao/sua theo thu tu:
  1. Constants (`App/Constants/...`)
  2. Migration (neu can)
  3. Model (`App/Models/...`)
  4. Repository & Criteria (`App/Repositories/...`)
  5. Service (`App/Services/...`)
  6. Request (`App/Http/Requests/...`)
  7. Resource (`App/Http/Resources/...`)
  8. Controller (`App/Http/Controllers/...`)
  9. Route (`routes/api/...`)
  10. Test (`tests/Feature/...`)

## 3. Strict Implementation Loop

### 3.1. Infrastructure Layer
- Tao Constants truoc (Tranh magic strings)
- Tao/Update Migration & Model
- Tao Repository & Binding trong ServiceProvider
- Tao `SortAndFilter{Entity}Criteria` neu co API list/search

### 3.2. Service Layer
- Viet Business Logic trong Service extends AbstractService
- BAT BUOC: Wrap write operations trong transaction (`beginTransaction/commitTransaction/rollbackTransaction`)
- BAT BUOC: Dung Criteria cho list methods
- BAT BUOC: Throw `BusinessException` (kem `ExceptionCode`) cho loi logic

### 3.3. HTTP Layer
- Tao Request Validation (dung RequestTrait, renderMessageFromRule)
- Tao Resource (dung Constants de map fields, whitelist approach)
- Tao Controller (CHI goi Service, khong logic)
- Dung `Response::success/created/failure/pagination`

### 3.4. Routing
- Dang ky route trong `routes/api/{module}.php`
- Apply middleware: `['auth:admin']` hoac `['auth:api']` dung context

## 4. Code Quality Gate

Truoc khi chuyen sang testing:
- [ ] Khong co magic strings (dung Constants)
- [ ] Khong co `response()->json()` (dung Response facade)
- [ ] Khong co direct Eloquent trong Controller/Service
- [ ] IDOR check trong moi read/write operation
- [ ] Resource dung Constants, khong return Model truc tiep
- [ ] BusinessException cho moi business logic error

## 5. Testing

### Tao Test File: `tests/Feature/{Module}/{Entity}Test.php`

### Test Cases BAT BUOC cho moi endpoint:
- Success (200/201): Happy path voi valid data
- Validation (422): Missing/invalid fields
- Auth (401): Khong co token
- Auth (403): Khong co permission
- Business Logic (400): Edge cases

### Assertion Pattern:
```php
$response->assertStatus(200);
$response->assertJsonStructure(['code', 'data' => [...]]);
// MUST assert ALL fields from request body
```

### Chay Test:
```bash
php artisan test --filter {TestClassName}
```

## 6. Debug Loop (Neu test fail)

1. Doc error output + stack trace
2. Doi chieu voi spec
3. Sua code (KHONG comment out test de pass)
4. Chay lai test
5. Lap lai cho den khi GREEN

## 7. Finalize

- Bao cao ket qua + danh sach file da tao
- Goi y next steps: `/api-test` de verify, `/docs-sync` de update docs
