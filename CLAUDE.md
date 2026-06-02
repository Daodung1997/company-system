# CLAUDE.md - ViecVat AI Backend

> Project configuration cho Claude Code. File nay duoc doc tu dong moi conversation.

---

## 1. LANGUAGE & COMMUNICATION

- **User Language**: Vietnamese (Tieng Viet)
- **Code/Comments**: English
- **Style**: Direct, professional, no fluff
- **Diagram**: Always use PlantUML

---

## 2. PROJECT OVERVIEW

- **Project**: ViecVat AI Backend
- **Tech Stack**: Laravel (PHP), MySQL, JWT Auth (`tymon/jwt-auth`)
- **Architecture**: Controller -> Service (AbstractService) -> Repository (Criteria) -> Model (BaseModel)
- **Database Naming**: `m_` (master data), `t_` (transaction data)
- **API Response Format**:
```json
{
  "code": 200,
  "data": {...}
}
```

---

## 3. CODING CONVENTIONS (MANDATORY)

### 3.1. Response Pattern
```php
// ALWAYS use
use App\Supports\Facades\Response\Response;
return Response::success($data);
return Response::created($data);
return Response::failure(['message' => 'error.key'], 400);
return Response::pagination($collection, $total, $page, $limit);

// NEVER use
return response()->json($data);
return new JsonResponse($data);
```

### 3.2. Model Rules
```php
// Business entities -> BaseModel
class Order extends BaseModel {}

// Master data with auto-code -> BaseMasterModel
class Product extends BaseMasterModel
{
    const PREFIX_CODE = 'PRD';
}
```
- KHONG tu set audit fields (`created_by`, `updated_by`) - BaseModel boot() tu xu ly.

### 3.3. Constants Pattern - KHONG dung magic strings
```php
// WRONG
$user->status = 'active';

// CORRECT
use App\Constants\Commons\CommonStatusConst;
$user->status = CommonStatusConst::ACTIVE;
```

**Constants structure:**
```
Constants/
├── Commons/              # Shared across modules
├── Master/
│   └── Models/{Entity}/
│       ├── {Entity}Column.php
│       ├── {Entity}Relation.php
│       └── {Entity}ResourceConst.php
└── Platform/             # Platform-specific
```

### 3.4. Repository Usage - Fluent chain pattern
```php
$this->userRepository
    ->pushCriteria(new ActiveUserCriteria())
    ->with(['roles'])
    ->findWhere(['type' => 'admin'])
    ->paginate(20);
```
- `->available()` = Exclude DELETED
- `->active()` = Exclude DELETED + INACTIVE
- KHONG dung Eloquent truc tiep trong Controller/Service

### 3.5. Service Layer - Transaction handling
```php
class OrderService extends AbstractService
{
    public function createOrder($data)
    {
        $this->beginTransaction();
        try {
            // logic
            $this->commitTransaction();
            return $result;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
```

### 3.6. Request Validation - LUON dung RequestTrait
```php
use App\Traits\RequestTrait;

class StoreOrderRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
```

### 3.7. Exception Handling
```php
// WRONG
throw new Exception("User not found", 404);

// CORRECT
use App\Exceptions\BusinessException;
use App\Constants\Commons\ExceptionCode;
throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'User not found in system', 404);
```

### 3.8. Resource Transformation - Dung Constants
```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = [];
        foreach (UserResourceConst::getValues() as $value) {
            if (!in_array($value, UserRelation::getValues())) {
                $resource[$value] = $this->{$value};
            }
        }
        foreach (CommonResourceConst::getValues() as $value) {
            $resource[$value] = $this->{$value};
        }
        return $resource;
    }
}
```

### 3.9. Controller - Thin controllers only
```php
class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create($request->validated());
        return Response::created(['order' => new OrderResource($order)]);
    }
}
```

### 3.10. File Organization
```
1. Controller  -> Controllers/{Module}/{Entity}Controller.php
2. Service     -> Services/{Module}/{Entity}Service.php
3. Request     -> Requests/{Module}/{Entity}/Store{Entity}Request.php
4. Resource    -> Resources/{Module}/{Entity}/{Entity}Resource.php
5. Constants   -> Constants/Master/Models/{Entity}/*.php
6. Route       -> routes/api/{module}.php
```

### 3.11. Naming Conventions

| Element | Pattern | Example |
|---------|---------|---------|
| Controller | `{Entity}Controller` | `OrderController` |
| Service | `{Entity}Service` | `OrderService` |
| Request | `{Action}{Entity}Request` | `StoreOrderRequest` |
| Resource | `{Entity}Resource` | `OrderResource` |
| Criteria | `{Description}Criteria` | `ActiveUserCriteria` |
| Const | `{Entity}{Type}Const` | `OrderStatusConst` |

---

## 4. SECURITY (CRITICAL)

### IDOR Prevention
```php
// WRONG
$order = Order::find($id);

// CORRECT: Scope by owner
$order = $this->orderRepository->findWhere([
    'id' => $id,
    'user_id' => auth()->id()
]);
```

### Data Leakage Prevention
```php
// WRONG: Return Model directly
return Response::success($user);

// CORRECT: Always use Resource
return Response::success(new UserResource($user));
```

### Authentication - JWT patterns
```php
$token = Auth::guard(CommonConst::API)->attempt($credentials);
$userCode = auth()->user()->code;
JWTAuth::parseToken()->invalidate(true);
```

- All public APIs MUST use `throttle` middleware
- Internal: `auth:api` middleware

---

## 5. REUSABLE CORE COMPONENTS

Truoc khi code, CHECK cac component co san:

### Facades & Utils
- `App\Supports\Facades\Response\Response` - Response facade (BAT BUOC)
- `App\Utils\Helper` - Static utility functions

### Constants System
- `App\Constants\Commons\CommonConst` - API guard, BEARER token
- `App\Constants\Commons\CommonStatusConst` - ACTIVE, INACTIVE, DELETED
- `App\Constants\Commons\CommonLocaleConst` - Locales
- `App\Constants\Commons\CommonTokenConst` - JWT token keys
- `App\Constants\Master\Models\{Entity}\*` - Entity-specific constants

### Base Classes
- `App\Models\BaseModel` - Business entities (auto audit trails, scopes)
- `App\Models\BaseMasterModel` - Master data (auto code generation)
- `App\Traits\RequestTrait` - Auto validation messages
- `App\Repositories\Repository` - Base repository with Criteria
- `App\Services\AbstractService` - Transaction helpers
- `App\Services\Common\BaseAuthService` - Auth foundation

### Middleware
- `VerifyJWTToken` - JWT authentication
- `PermissionMiddleware` - Permission check
- `LocaleMiddleware` - Locale from header

### Image/File Handling
- `App\Services\Common\UploadService` - Upload, resize, optimize
- `App\Models\Image` - Table `t_images`, prefix `IMG`
- Pattern: DB luu `{field}_code` -> Model hasOne(Image) -> Resource: `$this->icon->getUrl()`

---

## 6. DEFINITION OF DONE

Task KHONG duoc coi la complete cho den khi:
1. Code follows coding conventions (section 3)
2. Unit Tests > 80% coverage cho logic moi
3. Linter passes
4. Security: IDOR check & Data Leakage prevention
5. Dung Repository pattern, Constants, RequestTrait, Response facade

---

## 7. SAFE PRINCIPLES

- **Built-in Quality**: Khong trade quality for speed
- **Transparency**: Generate clear artifacts (Plans, Logs)
- **Program Execution**: Focus on specific goal, khong gold-plate
- Check existing "Architectural Runway" (reusable components) truoc khi code
- Flag risks to release immediately

---

## 8. WORKING PROCESS

### Socratic Gate (Before Implementation)
STOP & ASK neu:
1. Requirements < 90% clear
2. High-risk changes (Auth, Money, Data structure)
3. Edge cases undefined

### Execution Modes
| Mode | Action |
|------|--------|
| PLANNING | Analyze -> Research -> Create plan. NO CODE. |
| EXECUTION | Implement per Plan -> Write Code -> Tests |
| VERIFICATION | Verify vs Requirements -> Run Tests -> Review |

---

## 9. AVAILABLE COMMANDS

Cac slash commands co san trong `.claude/commands/`:

### Implementation
- `/implement` - Implement feature theo strict spec + conventions
- `/code` - Viet code theo spec hoac agile mode
- `/change-request` - Xu ly ad-hoc change request (docs-first)

### Testing & Debug
- `/api-test` - Tao va chay API test cases
- `/debug-api` - Debug API failure systematically
- `/debug-test` - Debug failed unit tests

### Code Quality
- `/review` - Code review theo conventions & security
- `/verify-spec` - Cross-check Functional Spec vs Origin Spec
- `/docs-sync` - Sync documentation voi code
- `/response-sync` - Sync API response docs voi actual code

### Business Analysis
- `/ba-discovery` - App-level analysis (docs/app/*)
- `/ba-delivery` - Feature-level specs (docs/features/<slug>/*)
- `/ba-hybrid` - Discovery + selected feature delivery

### Design
- `/stitch-design` - UI/UX design voi Google Stitch MCP

---

## 10. QUICK CHECKLIST

Truoc khi submit code:
- [ ] Dung `Response::` facade
- [ ] Extends dung `BaseModel` hoac `BaseMasterModel`
- [ ] Dung Constants thay vi magic strings
- [ ] Dung `RequestTrait` trong FormRequest
- [ ] Dung Repository pattern
- [ ] Service extends `AbstractService` cho transactions
- [ ] Resource dung Constants de map fields
- [ ] Dat file dung folder theo module
- [ ] Naming dung convention
- [ ] Security: IDOR check & Data Leakage prevention
- [ ] BusinessException voi ExceptionCode cho logic errors
