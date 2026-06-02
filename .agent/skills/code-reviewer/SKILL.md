---
name: code-reviewer
description: Review source code for compliance with project conventions, security standards, and architectural patterns.
---

# Code Review Skill

> **Goal**: Ensure every line of code merging into the codebase strictly adheres to `coding-conventions.md`, maintains high security standards, and follows the defined architecture.

---

## 1. 📥 Context & Setup

**Action**: Before reviewing, understand the scope and related standards.

1.  **Read the Diff**: Identify all changed files.
2.  **Read Conventions**:
    - `/.agent/rules/coding-conventions.md` (The Law)
    - `/.agent/skills/feature-implementer/SKILL.md` (The Workflow)
3.  **Identify Module**: Determine which module is being touched.

---

## 2. 🔍 Architecture & Structure Review

### 2.1 File Organization
- [ ] **Location**: Are files in the correct directory structure?
    - Controllers: `src/app/Http/Controllers/{Module}/`
    - Services: `src/app/Services/{Module}/`
    - Requests: `src/app/Http/Requests/{Module}/{Entity}/`
    - Resources: `src/app/Http/Resources/{Module}/{Entity}/`
- [ ] **Naming**: Do filenames match the Class Name and adhere to `{Entity}{Type}` pattern?
    - `OrderController`, `OrderService`, `StoreOrderRequest`, `OrderResource`.

### 2.2 Layer Responsibilities
- [ ] **Controller**: thinner is better.
    - ❌ NO logic.
    - ❌ NO direct Model access (use Service).
    - ❌ NO `response()->json()` (Use `Response::` facade).
    - ✅ Should only handle Request -> Service -> Response.
- [ ] **Service**:
    - ✅ Extends `AbstractService`.
    - ✅ Logic goes here.
    - ✅ Wraps writable actions in `beginTransaction` / `commitTransaction`.
    - ❌ NO direct SQL/Model calls for standard operations (Use Repository).
- [ ] **Repository**:
    - ✅ Used for all DB interactions.
    - ✅ Uses `Criteria` for filtering/sorting list endpoints.

---

## 3. 🛡️ Security & Validation Review (CRITICAL)

### 3.1 IDOR (Insecure Direct Object References)
- [ ] **Ownership Check**: Does the code verify that the user owns the resource they are accessing/modifying?
    - ✅ `findWhere(['id' => $id, 'user_id' => auth()->id()])`
    - ✅ `Request` validation: `'exists:orders,id,user_id,' . auth()->id()`
    - ❌ `Order::find($id)` without user check.

### 3.2 Request Validation
- [ ] **RequestTrait**: Does the FormRequest use `App\Traits\RequestTrait`?
- [ ] **Messages**: Does it implement `messages()` using `renderMessageFromRule`?
- [ ] **Rules**: Are rules strict enough?
    - string max lengths?
    - valid enums?
    - numeric ranges?

### 3.3 Data Leakage & Resource Consistency
- [ ] **Resources**: Are `JsonResource` classes used for ALL responses?
    - ❌ NO returning Models directly.
    - ✅ Explicit attribute mapping for scalar fields.
    - ❌ **NO manual array mapping for relationships**. You MUST always reuse the corresponding Resource class (e.g., `'customer' => new UserResource($this->customer)` instead of manually creating an array).
    - ❌ NO `fail($exception)` exposing stack traces to users.

### 3.4 Exception Handling
- [ ] **Business Logic**: Are `BusinessException` used for logical failures?
    - ✅ `throw new BusinessException(ExceptionCode::CODE, ...)`
    - ❌ `throw new Exception(...)`
    - ❌ `return response()->json(...)` inside Service.

---

## 4. 🧹 Code Quality & Conventions

### 4.1 Constants
- [ ] **Magic Strings**: Are there hardcoded strings?
    - ❌ `$status = 'active'`
    - ✅ `$status = CommonStatusConst::ACTIVE`
    - ❌ `if ($type == 1)`
    - ✅ `if ($type == TypeConst::ADMIN)`
- [ ] **Column Names**: String literals are allowed for columns, but usage of specific Column constants is deprecated.

### 4.2 Response Format
- [ ] **Facade**: Is `App\Supports\Facades\Response\Response` used?
    - `Response::success($data)`
    - `Response::created($data)`
    - `Response::pagination(...)`

### 4.3 Clean Code
- [ ] **Type Hinting**: Are arguments and return types typed?
- [ ] **Comments**:
    - Code is in English.
    - Complex logic has explanation.
    - No commented-out code left behind.

---

## 5. 🧪 Testing Review

- [ ] **Existence**: Do new features have corresponding tests?
    - `src/tests/Feature/{Module}/{Entity}Test.php`
- [ ] **Coverage**:
    - ✅ Success case (200/201).
    - ✅ Validation failure (422).
    - ✅ Auth failure (401/403).
    - ✅ Logic failure (400) if applicable.
- [ ] **Assertions**:
    - `assertJsonStructure` validation.
    - `assertDatabaseHas` for mutations.
    - `assertStatus` checks.

---

## 6. 📝 Feedback Format

**Action**: When providing feedback, be specific and reference the rule.

**Template**:
```markdown
### ❌ [Severity: BLOCKER/MAJOR/MINOR]
**File**: `path/to/file.php`
**Issue**: [Description of strict violation]
**Rule**: [Reference to Coding Convention e.g. "Response Pattern", "IDOR"]
**Suggestion**:
```php
// Old Code
...
// Suggested Fix
...
```

### ✅ [Review Summary]
- [ ] Architecture: [Pass/Fail]
- [ ] Security: [Pass/Fail]
- [ ] Standards: [Pass/Fail]
- [ ] Tests: [Pass/Fail]

**Conclusion**: [APPROVE / REQUEST CHANGES]
```

---

## 7. 🚀 Quick Checklist for Reviewer

1.  **Response Facade** used everywhere?
2.  **RequestTrait** in FormRequests?
3.  **AbstractService** + **Transactions** used?
4.  **BusinessException** used with **ExceptionCodes**?
5.  **Constants** used for statuses/logic?
6.  **Tests** cover main scenarios?
7.  **IDOR** checks present?
