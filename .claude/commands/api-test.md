# Tao va Chay API Tests

> Trigger: `/api-test [module or endpoint]`

## Response Format Reference

```php
// Success 200
{"code": 200, "data": {"entity": {...}}}

// Created 201
{"code": 201, "data": {"entity": {...}}}

// Pagination 200
{"code": 200, "data": {"items": [...], "total": 100, "page": 1, "limit": 20}}

// Validation Error 422
{"code": 422, "messages": {"field": ["field.required"]}}

// Business Error 400
{"code": 400, "messages": {"message": "Error description", "error_code": "ERROR_CODE"}}

// Auth Error 401
{"code": 401, "messages": {"message": "Unauthenticated"}}
```

## Test File Structure

- Path: `tests/Feature/{Module}/{Entity}Test.php`
- Extends: `Tests\TestCase`
- Traits: `RefreshDatabase`, `WithFaker`

## Test Cases Required (cho moi endpoint)

### CRUD Endpoints:
1. **Success** (200/201): Valid data, correct response structure
2. **Validation** (422): Missing required fields, invalid format
3. **Auth** (401): No token
4. **Permission** (403): Wrong role
5. **Not Found** (404): Invalid ID
6. **Business Logic** (400): Edge cases (duplicate, invalid state)

### List/Search Endpoints:
1. **Success** (200): Pagination structure correct
2. **Filter**: Query params work correctly
3. **Sort**: Ordering works
4. **Empty**: Returns empty array, not error

## Assertion Patterns

```php
// Structure assertion
$response->assertStatus(200)
    ->assertJsonStructure([
        'code',
        'data' => ['entity' => ['id', 'name', 'code', ...]]
    ]);

// Value assertion
$response->assertJson([
    'code' => 200,
    'data' => ['entity' => ['name' => $expected]]
]);

// Validation assertion
$response->assertStatus(422)
    ->assertJsonValidationErrors(['field_name']);

// Pagination assertion
$response->assertJsonStructure([
    'code',
    'data' => ['items' => [['id', 'name']], 'total', 'page', 'limit']
]);
```

## Execution

```bash
# Run specific test
php artisan test --filter {TestClassName}

# Run specific method
php artisan test --filter {testMethodName}

# Run with coverage
php artisan test --coverage --filter {TestClassName}
```

## IMPORTANT
- MUST assert ALL fields from request body in response (prevent field drops)
- MUST test IDOR: user A cannot access user B's data
- MUST test auth: endpoint requires correct guard (api/admin)
