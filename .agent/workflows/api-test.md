---
description: 🧪 Tạo Feature/Unit Test cho Laravel REST API
---

# WORKFLOW: /api-test - Laravel API Test Generator

> Tạo test cases cho REST API theo chuẩn ViecVat, dựa trên `docs/features/{module}` và `coding-conventions.md`.

---

## 1. 📥 Context Loading (MANDATORY)

**TRƯỚC KHI viết test, PHẢI đọc:**

1. **Feature Spec**: `docs/features/{module}/01_apis/{endpoint}/functional_spec.md`
   - Lấy: Endpoint, Request/Response format, Business Rules, Acceptance Criteria
2. **ORM Design**: `docs/app/database_schema.md`
   - Lấy: Table names, columns và Model để assert database
3. **Coding Conventions**: `.agent/rules/coding-conventions.md`
   - Đảm bảo test output đúng Response format

---

## 2. 📦 Response Format Reference (ViecVat Standard)

### Success (200)
```json
{ "code": 200, "data": { "entity": { ... } } }
```

### Created (201)
```json
{ "code": 201, "data": { "entity": { ... } } }
```

### Failure (4xx/5xx)
```json
{ "code": 400, "messages": ["error.key"] }
```

### Pagination
```json
{
  "code": 200,
  "data": {
    "data": [...],
    "total": 100,
    "current_page": 1,
    "limit": 20,
    "metadata": {}
  }
}
```

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": { "field_name": ["validation.required"] }
}
```

---

## 3. 🏗️ Test File Structure

### Path Convention
```
src/tests/Feature/{Module}/{Entity}Test.php
src/tests/Unit/{Module}/{Entity}ServiceTest.php
```

### Base Template
```php
<?php

namespace Tests\Feature\{Module};

use Tests\TestCase;
use App\Models\User;
use App\Models\{Entity};
use Illuminate\Foundation\Testing\RefreshDatabase;

class {Entity}Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // Nếu cần admin
        // $this->admin = User::factory()->admin()->create();
    }

    // ── Helper Methods ──

    protected function authAs(User $user)
    {
        return $this->actingAs($user, 'api');
    }

    protected function assertSuccessResponse($response, int $code = 200)
    {
        $response->assertStatus($code)
            ->assertJsonStructure(['code', 'data']);
    }
}
```

---

## 4. ✅ Test Cases Required (Checklist)

### 4.1. Success Cases (Happy Path) - MANDATORY
- [ ] `test_can_list_{entities}` - GET list (200)
  - `assertJsonStructure(['code', 'data' => [...]])`
- [ ] `test_can_show_{entity}` - GET single (200)
  - `assertJsonPath('data.entity.id', $id)`
- [ ] `test_can_create_{entity}` - POST (201)
  - `assertDatabaseHas('table', [...])`
- [ ] `test_can_update_{entity}` - PUT (200)
  - `assertDatabaseHas` với giá trị mới
- [ ] `test_can_delete_{entity}` - DELETE (200)
  - `assertSoftDeleted('table', ['id' => $id])`

### 4.2. Validation Cases (422) - MANDATORY
Đọc `functional_spec.md` → Business Rules → Validation để biết fields cần test.

- [ ] `test_cannot_create_without_{required_field}`
- [ ] `test_cannot_create_with_invalid_{format_field}`
- [ ] `test_cannot_create_duplicate_{unique_field}`

### 4.3. Auth Cases (401/403) - IF APPLICABLE
- [ ] `test_guest_cannot_access_{protected_endpoint}` (401)
- [ ] `test_non_admin_cannot_access_admin_endpoint` (403)

### 4.4. Business Logic Cases (400) - FROM SPEC
Đọc `functional_spec.md` → Error Flows để biết các case lỗi logic.

- [ ] Test edge cases từ "Alternate / Error Flows" trong spec

---

## 5. 📝 Assertion Patterns

### Success Response
```php
$response->assertStatus(200)
    ->assertJson(['code' => 200])
    ->assertJsonStructure([
        'code',
        'data' => ['entity' => ['id', 'name']]
    ]);
```

### Created Response + Database Check
```php
$response->assertStatus(201)
    ->assertJson(['code' => 201]);

$this->assertDatabaseHas('m_table_name', [
    'name' => 'Test Value',
    'is_active' => true,
]);
```

### Pagination Response
```php
$response->assertStatus(200)
    ->assertJsonStructure([
        'code',
        'data' => [
            'data' => ['*' => ['id', 'name']],
            'total',
            'current_page',
            'limit',
        ]
    ]);
```

### Validation Error (422)
```php
$response->assertStatus(422)
    ->assertJsonValidationErrors(['field_name']);
```

### Failure Response (400/404/...)
```php
$response->assertStatus(400)
    ->assertJson([
        'code' => 400,
        'messages' => ['error.key']
    ]);
```

### Auth Check
```php
// Authenticated request
$response = $this->authAs($this->user)->getJson('/api/v1/endpoint');

// Guest should get 401
$this->getJson('/api/v1/protected')->assertStatus(401);
```

### Database Assertions
```php
$this->assertDatabaseHas('m_table', ['column' => 'value']);
$this->assertDatabaseMissing('m_table', ['id' => $id]);
$this->assertSoftDeleted('m_table', ['id' => $id]);
$this->assertDatabaseCount('m_table', 3);
```

---

## 6. 🚀 Execution Steps

### Step 1: 🛡️ Code Compliance Check (BLOCKER)
**TRƯỚC KHI viết test, PHẢI kiểm tra code (Controller/Service/Model) theo `coding-conventions.md`.**

Nếu vi phạm, **PHẢI FIX CODE TRƯỚC** rồi mới viết test.

**Checklist:**
- [ ] **Response**: Controller dùng `Response::success/created/failure`. KHÔNG dùng `response()->json()`.
- [ ] **Exceptions**: Service KHÔNG throw `Exception` generic với status code. Dùng custom classes hoặc `HttpResponseException` với format chuẩn.
- [ ] **Constants**: KHÔNG dùng magic strings cho keys, status, columns.
  - Vd: `UserColumn::NAME` thay vì `'name'`, `UserStatusConst::ACTIVE` thay vì `'active'`.
- [ ] **Model**: KHÔNG set properties trực tiếp nếu đã có `fillable`/`create`.
- [ ] **Repository**: Dùng Repository pattern nếu đã define.

> ❌ **NẾU CODE CHƯA ĐẠT CHUẨN -> STOP VÀ FIX NGAY.**

### Step 2: Read Feature Spec
```
docs/features/{module}/01_apis/{endpoint}/functional_spec.md
```
Extract: Endpoint, Request body, Response structure, Business Rules, Acceptance Criteria.

### Step 2: Create Test File
```bash
// turbo
php artisan make:test Feature/{Module}/{Entity}Test
```

### Step 3: Write Test Methods
Naming pattern: `test_{can/cannot}_{action}_{entity}_{scenario}`

Examples:
```
test_can_list_categories
test_can_create_category
test_cannot_create_category_without_name
test_cannot_create_duplicate_category_name
test_guest_cannot_access_admin_categories
```

### Step 4: Run Tests
```bash
// turbo
php artisan test tests/Feature/{Module}/{Entity}Test.php

// turbo
php artisan test --filter={Entity}Test

// With coverage
// turbo
php artisan test --coverage
```

---

## 7. 📄 Complete Example: Category API Tests

Từ `docs/features/service/01_apis/category/create_category/functional_spec.md`:

```php
<?php

namespace Tests\Feature\Service;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    // ══════════════════════════════════════════════════
    // SUCCESS CASES
    // ══════════════════════════════════════════════════

    public function test_admin_can_list_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/categories');

        $response->assertStatus(200)
            ->assertJson(['code' => 200])
            ->assertJsonStructure([
                'code',
                'data' => [
                    'categories' => [
                        '*' => ['id', 'name', 'slug', 'is_active']
                    ]
                ]
            ]);
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Cleaning Services',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['code' => 201])
            ->assertJsonPath('data.category.name', 'Cleaning Services');

        $this->assertDatabaseHas('m_categories', [
            'name' => 'Cleaning Services',
            'slug' => 'cleaning-services',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/categories/{$category->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.category.name', 'New Name');

        $this->assertDatabaseHas('m_categories', [
            'id' => $category->id,
            'name' => 'New Name',
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('m_categories', ['id' => $category->id]);
    }

    public function test_public_can_view_category_tree(): void
    {
        Category::factory()->count(2)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'categories' => [
                        '*' => ['id', 'name', 'slug']
                    ]
                ]
            ]);
    }

    // ══════════════════════════════════════════════════
    // VALIDATION CASES (422)
    // ══════════════════════════════════════════════════

    public function test_cannot_create_category_without_name(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_duplicate_category_name(): void
    {
        Category::factory()->create(['name' => 'Existing Category']);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Existing Category',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_set_category_parent_to_itself(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/categories/{$category->id}", [
                'parent_id' => $category->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    // ══════════════════════════════════════════════════
    // AUTH CASES (401/403)
    // ══════════════════════════════════════════════════

    public function test_guest_cannot_access_admin_categories(): void
    {
        $response = $this->getJson('/api/v1/admin/categories');

        $response->assertStatus(401);
    }
}
```

---

## 8. ✅ Checklist Before Submit

- [ ] Đọc `functional_spec.md` trước khi viết test
- [ ] Cover tất cả Success paths (200/201)
- [ ] Cover Validation errors (422) từ Request rules
- [ ] Cover Auth checks (401/403) nếu có
- [ ] Cover Business Logic errors (400) từ Error Flows trong spec
- [ ] `assertDatabaseHas`/`assertSoftDeleted` cho mutations
- [ ] Run `php artisan test` and all pass
- [ ] Test names follow pattern: `test_{can/cannot}_{action}_{entity}_{scenario}`