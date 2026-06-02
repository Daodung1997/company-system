---
name: feature-implementer
description: Implements features based on structured documentation (Module Map -> Feature Specs) and strict Project Coding Conventions, with robust Laravel testing + mandatory debug loops.
---

# Feature Implementation Skill (Upgraded)

> **Goal**: Turn documentation (`docs/`) into production-ready code (`src/`) following `coding-conventions.md`, and deliver reliable Laravel tests that can self-debug and converge to green.

---

## 1. 📥 Context Loading (Mandatory Step 1)

**Action**: Read the source of truth **before touching code**.

1. Read `docs/app/01_module_map.md` to understand module purpose, boundaries, dependencies.
2. Read feature docs under `docs/features/{module}/`:
   - **APIs**: `docs/features/{module}/01_apis/`
   - **DB**: `docs/app/database_schema.md`
   - **Flow** (if exists): `docs/features/{module}/01_apis/<api>/02_sequences.md`
3. Read **Project Rules (CRITICAL)**:
   - `/.agent/rules/coding-conventions.md`
4. Read **Common Components & Standards**:
   - `/.agent/rules/laravel-common-cores.md`
   - `/.agent/rules/resource-mapping.md` (CRITICAL for API Responses)
5. **Run Context Script (Automated File Discovery)**:
   - Run: `php .agent/skills/feature-implementer/scripts/resolve_module_files.php --module={ModuleName}`
   - **Action**: Use the output file paths to `view_file` the relevant code immediately.

**Output requirement**: Summarize (for self) module scope + entities + endpoints + tables involved + constraints.

---

## 2. 📝 Planning Mode

**Action**: Create `implementation_plan.md` containing:

### 2.1 Files to Create/Modify
- Controller: `src/app/Http/Controllers/{Module}/{Entity}Controller.php`
- Service: `src/app/Services/{Module}/{Entity}Service.php`
- Request:
  - `src/app/Http/Requests/{Module}/{Entity}/{Action}{Entity}Request.php`
- Resource:
  - `src/app/Http/Resources/{Module}/{Entity}/{Entity}Resource.php`
- Routes:
  - `src/routes/api/{module}.php`
- Model:
  - `src/app/Models/{Entity}.php`
- Migration:
  - Generate via `php artisan make:model {Entity} -m` (or migration only if model exists)

### 2.2 Database Verification
- Verify migrations/models match `docs/app/database_schema.md`.
- **CRITICAL**: Cross-reference the `Request Body` and `Response Body` in the Functional Specs (`docs/features/{module}/01_apis/`) against the Database Schema. If the spec requires a field (e.g. `reason`), the DB table MUST have a corresponding column.
- Confirm columns, indexes, soft deletes, FK relationships.
- If docs and code diverge, **decide**:
  - Update migration/model to match docs **or**
  - Update docs to match real requirement (but never leave divergence).

### 2.3 Step-by-Step Execution Plan (default order)
1) Constants → 2) Migration/Model → 3) Repository → 4) Service → 5) Request → 6) Resource → 7) Controller → 8) Routes → 9) Tests → 10) OpenAPI → 11) Docs sync

---

## 3. 💻 Implementation Rules (Strict)

**Apply `coding-conventions.md` to every file.**  
No “close enough”.

### 3.1 Constants First (No Magic Strings for Logic)
- **NEVER** hardcode:
  - statuses, types, roles, enums
- **ALLOWED**:
  - String literals for **Column Names** (e.g. `'email'`, `'created_at'`)
  - String literals for **Resource Keys** (e.g. `'id'`, `'full_name'`)
  - String literals for **Route Parameters**
- Define under:
  - `src/app/Constants/Master/Models/{Entity}/`
    - `StatusConst.php` (if needed)
    - `TypeConst.php` (if needed)

### 3.2 Model & Repository
- **Model** must extend `BaseModel` or `BaseMasterModel`.
- **Repository** follows `App\Repositories\Repository` pattern:
  - Inject Repo into Service (NOT Controller)
  - **MANDATORY**: Use `SortAndFilter{Entity}Criteria` for list endpoints.
    - Class: `App\Repositories\Criteria\{Module}\{Entity}\SortAndFilter{Entity}Criteria`
    - Extends: `AbstractSortAndFilterCriteria`
    - Implements: `CriteriaInterface`
    - Traits: `SortFilterSearchCriteria`
    - **Structure**:
      ```php
       public function apply($model, RepositoryInterface $repository) :BaseMasterModel|Builder|null
      {
          $select = ['*'];
          $relationship = []; // Add relations if needed
          $model = $this->filter($model);
          $model = $this->search($model);
          $model = $this->sort($model);
          return $model->select($select)->with($relationship);
      }

      public function sort($builder)
      {
          if (empty($this->sorts)) {
              $this->sorts = ['id' => 'desc'];
          }
          
          return $this->sortByConditions($builder, $this->sorts, [
              'id' => 'table_name.id',
              'code' => 'table_name.code',
              'created_at' => 'table_name.created_at',
          ]);
      }

      public function filter($builder)
      {
          return $this->filterByConditions($builder, $this->filters, [
              'status' => 'table_name.status',
              'role' => 'table_name.role',
          ]);
      }

      public function search($builder)
      {
           return $this->searchByConditions($builder, $this->searchConditions, [
               'name' => "m_departments.name",
               'code' => "m_departments.code",
               'branch_name' => "m_branches.name",
           ]);
      }
      ```

### 3.3 Service Layer
- Service extends `AbstractService`.
- **NEVER** access Models directly in Service (e.g., `Model::where()`, `Model::create()`).
  - **USE** injected Repository methods instead
  - **EXCEPTION**: Pivot/junction tables (e.g., `WorkerService`, `WorkerArea`) can use direct Model access if no dedicated repository exists
  - For complex queries, add custom methods to Repository
- All modifications wrapped in transaction:

```php
$this->beginTransaction();
try {
    // do work via repository
    $this->repository->create($data);
    $this->documentRepository->linkDocumentsToProfile($ids, $profileId);
    $this->commitTransaction();
} catch (\Throwable $e) {
    $this->rollbackTransaction();
    throw $e;
}
```

### 3.4 Exception Handling (Standardized)
- **MANDATORY**: Convert logical/business errors to `App\Exceptions\BusinessException`.
- **NEVER** use `HttpResponseException` directly in Service.
- Use `App\Constants\Commons\ExceptionCode` for error codes.

```php
if (!$user) {
    throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'User not found', 404);
}
```

- **Avoid** generic `Exception` for business logic.
- Let `App\Exceptions\Handler` render the response.

- **No business logic in Controllers**.
- Keep service methods small, named by use-case.
- **List Method Standard**:
  ```php
  public function list(Request $request): LengthAwarePaginator
  {
      $filters = $request->query('filters', []);
      $sorts = $request->query('sorts', []);
      $search = $request->query('search', []);
      $limit = $request->query('limit', App::PER_PAGE);

      return $this->repository->pushCriteria(
          new SortAndFilter{Entity}Criteria($filters, $sorts, $search)
      )->paginate($limit);
  }
  ```

### 3.4 Request Validation
- Use `use App\Traits\RequestTrait;`
- Messages:
  - `return $this->renderMessageFromRule($this->rules());`.
- Security:
  - Enforce IDOR checks (ownership) where applicable:
    - `exists:table,id,user_id,` . auth()->id()
  - Never trust route params blindly.

### 3.5 Response & Resources
- Use facade:
  - `App\Supports\Facades\Response\Response`
- Resource:
  - Use **explicit string keys** (e.g. `'full_name' => ...`).
  - **Do not use Constants** for resource keys.
  - Verify no data leakage (hidden fields, internal IDs, etc).
  - **MANDATORY**: Read `/.agent/rules/resource-mapping.md` before creating or mapping relationships.
  - **MANDATORY**: NEVER map array manually for relationships. Use explicit Resource classes (e.g., `new UserSimpleResource($this->whenLoaded('user'))`).
  - Eager load all relationships at the Service/Repository layer to avoid N+1 queries. Create and use "Thin Resources" (e.g., `*SimpleResource`) if full relationships aren't needed.
  - **NEVER** use inline `->map(function(...) { return [...] })` for relationships/nested arrays.
  - **ALWAYS** extract nested relationships into their own dedicated Resource classes and use `Resource::collection(...)`.
- Return consistent response envelope required by project.

### 3.6 Image & File Handling
- **Pattern**: Upload First -> Store Code.
- **NEVER** store Base64 strings or binary in main entity tables.
- **NEVER** handle file upload logic inside Entity Controller (single responsibility).
- **Steps**:
  1. Use/Create `UploadController` (if custom logic needed) to handle `multipart/form-data`.
  2. Use `App\Services\Common\UploadService` to save file -> returns `Image` model with `code`.
  3. Client sends `code` (e.g., `IMG001`) to Entity Create/Update endpoint.
  4. Entity Request validates `exists:t_images,code`.
  5. Entity Model stores `image_code`.
  6. Resource returns `{field}_url` via relationship `$this->image->getUrl()`.

---

## 4. 🏁 Verification (Code Quality Gate)

**Action**: Run `python scripts/checklist.py` if present; otherwise manual check against Quick Checklist in conventions.

Checklist:
1. Lint/style matches project conventions
2. Inputs validated (Request) + IDOR checks correct
3. Transaction used for modifications
4. No magic strings (Status/Type constants used)
5. Resource does not leak sensitive fields
6. Route + middleware + auth guard correct
7. Feature matches docs spec (fields, flow, errors)

---

## 5. 🧪 Testing Strategy (Mandatory, Upgraded)

> **Rule**: Tests must be **diagnosable**. A failing test must make it obvious what broke and where.

### 5.1 Test Types & Priority
1) Feature Tests (Integration) **mandatory**  
2) Unit tests optional (only for complex domain logic)

### 5.2 Paths
- `src/tests/Feature/{Module}/{Entity}Test.php`

### 5.3 Coverage Requirements (Minimum)
For each endpoint / use case:

#### A) Success (200/201)
Must include **at least 3 signals**:
1. `assertStatus(200|201)`
2. Contract assertions:
   - `assertJsonStructure(...)`
   - and/or `assertJsonPath(...)`
3. Side-effect assertions:
   - `assertDatabaseHas(...)` / `assertDatabaseMissing(...)`
   - **CRITICAL**: You MUST assert EVERY field that was sent in the request body (e.g., if the spec sends `status` AND `reason`, your `assertDatabaseHas` must check BOTH fields, not just the primary one).
   - `assertSoftDeleted(...)` when relevant  
Optional:
- `Event::fake()` / `Queue::fake()` / `Notification::fake()` then assert dispatched

#### B) Validation (422)
- Must assert:
  - status 422
  - error payload shape
  - the exact field keys in `errors`
- Include cases:
  - required fields missing
  - format invalid
  - exists/ownership rule violation

#### C) Auth (401/403)
- 401: guest
- 403: signed-in but unauthorized (policy/role/ownership)

#### D) Logic (400)
- Business rule violation scenarios (when spec says 400)

### 5.4 Test Harness Standardization (Recommended)
If project allows, create a base test helper:

- `src/tests/TestCase.php` already exists; add helpers via Trait or Base class:
  - `signIn()` / `asUser($user = null)` sets correct guard
  - `apiHeaders()` sets:
    - `Accept: application/json`
    - auth header when needed
  - `assertApiSuccess($response)` / `assertApiError($response)`
  - `assertValidationError($response, $field)`

**Rule**: No duplicated boilerplate across tests.

### 5.5 Query Parameter Structure (CRITICAL FOR LIST APIs)
When testing `GET` endpoints that involve filtering and searching, **YOU MUST** format the query string exactly as the Frontend sends it. 
Often, standard flat parameters (`?status=active`) are **WRONG**.
If a `FormRequest` expects array-based parameters (like `filters` or `search`), the test URL **MUST** reflect nested arrays:

- **INCORRECT (False Positive Risk)**: `/api/admin/workers?profile_status=approved&keyword=abc`
- **CORRECT**: `/api/admin/workers?filters[profile_status]=approved&search[keyword]=abc`

**Why?** A test using flat parameters might magically pass if logic is overly loose, or fail to catch bugs where the API strictly expects `filters` but ignores flat params. Always read the valid `List{Entity}Request` array keys before writing the URL string in the test.

### 5.6 Strict Side-Effect Assertions (Preventing Spec Gaps)
When an API Functional Spec dictates multiple fields in a Request Body (e.g. `{ "activity_status": "suspended", "reason": "Bad content" }`), a Unit Test MUST NOT pass if it only checks a subset of those side-effects.

**Why?** If you only assert `assertDatabaseHas` for `activity_status`, the test will pass EVEN IF the backend completely missed implementing the `reason` field in the Controller, Service, and Migration.

**Rule**: The array passed to `assertDatabaseHas` must be a 1:1 mapping of all relevant state changes requested by the payload to ensure zero fields are dropped during execution.

---

## 6. 🔧 Mandatory Debug Loop (When Any Test Fails)

> **Hard rule**: No “guess fixes”. Always extract evidence first.

### 6.1 Reproduce Minimally
Run a single test only:
- `php artisan test --filter <TestName> --stop-on-failure -vvv`

### 6.2 Dump Evidence (choose based on failure)
- If status != expected:
  - `$response->dump();`
  - `$response->dumpHeaders();` (if needed)
- If 422:
  - dump and verify `errors` keys
- If 500:
  - add `$this->withoutExceptionHandling();` temporarily to show stacktrace

### 6.3 Classify Failure → Fix at Correct Layer
- 404: route/method/prefix/middleware group
- 401: actingAs missing or wrong guard
- 403: policy/gate/ownership (IDOR rule)
- 422: FormRequest rules/payload mapping/content-type
- 500: migration/schema mismatch, null relation, repo binding, constant keys, transaction issues

### 6.4 Re-run & Converge
- Re-run the same failing test until green
- Then run module test suite
- Finally run full test suite if required by project

**Rule**: Remove temporary debug dumps and `withoutExceptionHandling()` once fixed.

---

## 7. 🧠 Common Failure Playbook (Quick Diagnosis Map)

### 7.1 404 Not Found
- Wrong route file (`src/routes/api/{module}.php`)
- Wrong prefix/group
- Wrong HTTP method
- Missing middleware group registration

### 7.2 401 Unauthorized
- Forgot `actingAs($user)`
- Wrong guard (`sanctum` vs `api`)
- Missing `Authorization` header

### 7.3 403 Forbidden
- Policy/Gate denies
- Ownership check (IDOR) denies
- Service layer authorization check denies

### 7.4 422 Validation Error
- Missing required fields
- Wrong request payload key names
- Wrong `exists` table/column
- Wrong JSON headers / content-type

### 7.5 500 Internal Error
- DB column missing / migration mismatch
- Null property access from optional relation
- Wrong repository binding / container resolution error
- Resource mapping using wrong constant key
- Transaction not committed or exception swallowed

---

## 8. 📖 API Documentation (OpenAPI YAML)

**Action**: Update `docs/api/openapi.yaml`.

Rules:
1. Add new paths under `paths:`
2. Define schemas under `components.schemas`
3. Group endpoints using `tags`
4. Include examples request/response
5. Add security section when authenticated

Template:

```yaml
/module/endpoint:
  post:
    tags:
      - ModuleName
    summary: Short description
    security:
      - bearerAuth: []
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            required:
              - field1
            properties:
              field1:
                type: string
    responses:
      '200':
        description: Success
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ResponseSchema'
      '401':
        description: Unauthorized
```

View docs:
- `cd docs/api && php -S localhost:8080`

---

## 9. 🔄 Documentation Synchronization (Mandatory)

**Rule**: Code and Docs must never diverge.

After implementing:
1. **DB**
   - If migrations/models changed, update `docs/app/database_schema.md`
   - Update relationships / ERD overview if needed
2. **API**
   - Ensure `docs/api/openapi.yaml` reflects actual request/response and fields
3. **Logic**
   - If flow changed, update `docs/features/{module}/...` sequences/specs
x
---

## 10. ✅ Deliverables Checklist (Definition of Done)
- Implementation plan exists (`implementation_plan.md`)
- All code follows `coding-conventions.md`
- All endpoints implemented + wired routes
- Feature tests cover success/422/401/403/400 as applicable
- Tests are green
- OpenAPI updated
- Docs synced
- All debug dumps removed
