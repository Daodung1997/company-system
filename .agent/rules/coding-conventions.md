# Coding Conventions for AI Agent

> **MANDATORY**: Agent PHẢI tuân thủ các rules này khi viết code cho project ViecVat.

---

## 1. Response Pattern

### ALWAYS use Response Facade
```php
// ✅ LUÔN DÙNG
use App\Supports\Facades\Response\Response;

return Response::success($data);
return Response::created($data);
return Response::failure(['message' => 'error.key'], 400);
return Response::pagination($collection, $total, $page, $limit);

// ❌ KHÔNG BAO GIỜ dùng các hàm built-in của Laravel
return response()->json($data);
return new JsonResponse($data);

### Pagination Warning (CẢNH BÁO PHÂN TRANG)
// ❌ KHÔNG BAO GIỜ dùng Response::success() cho dữ liệu dạng phân trang (trả về từ paginate()). Điều này sẽ làm mất metadata (total, current_page,...) khiến FE bị crash hoặc hiển thị bảng trống do thiếu res.data.data.data.
return Response::success($paginatedData);

// ✅ LUÔN DÙNG Response::pagination() cho dữ liệu list phân trang:
return Response::pagination($collection, $total, $page, $limit);
```

---

## 2. Model Rules

### Extends đúng Base Class
```php
// Business entities → BaseModel
class Order extends BaseModel {}

// Master data cần auto-code → BaseMasterModel
class Product extends BaseMasterModel 
{
    const PREFIX_CODE = 'PRD';
}
```

### KHÔNG tự set audit fields
```php
// ❌ WRONG - BaseModel tự xử lý
$model->created_by = auth()->user()->code;

// ✅ CORRECT - Để BaseModel boot() handle
$model->save();
```

---

## 3. Constants Pattern

### KHÔNG dùng magic strings
```php
// ❌ WRONG
$user->status = 'active';
$column = 'email';

// ✅ CORRECT
use App\Constants\Commons\CommonStatusConst;
use App\Constants\Master\Models\User\UserColumn;

$user->status = CommonStatusConst::ACTIVE;
$column = UserColumn::EMAIL;
```

### Khi tạo Constants mới
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

---

## 4. Repository Usage

### Fluent chain pattern
```php
// ✅ CORRECT
$this->userRepository
    ->pushCriteria(new ActiveUserCriteria())
    ->with(['roles'])
    ->findWhere(['type' => 'admin'])
    ->paginate(20);

// ❌ WRONG - Direct Eloquent trong Controller/Service
User::where('status', 'active')->get();
```

### Available scopes
```php
->available()  // Exclude DELETED
->active()     // Exclude DELETED + INACTIVE
```

---

## 5. Service Layer

### Transaction handling
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

---

## 6. Request Validation

### LUÔN dùng RequestTrait
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

---

## 7. Exception Handling

### Standardized Business Exceptions
- **MANDATORY**: Dùng `BusinessException` cho lỗi logic.
- **ERROR CODES**: Dùng `ExceptionCode::CONSTANT`.
- **HANDLER**: Để Global Handler render JSON.

```php
// ❌ WRONG
throw new Exception("User not found", 404);
throw new HttpResponseException(response()->json(...));

// ✅ CORRECT
use App\Exceptions\BusinessException;
use App\Constants\Commons\ExceptionCode;

throw new BusinessException(
    ExceptionCode::USER_NOT_FOUND, 
    'User not found in system', 
    404
);
```

### Response Format (Automatic)
```json
{
    "code": 404,
    "messages": {
        "message": "User not found in system",
        "error_code": "USER_NOT_FOUND"
    }
}
```

---

## 7. Resource Transformation

### Nested Relationships (Relations)
**MANDATORY**: Luôn tạo Resource riêng cho các relationships và sử dụng `Resource::collection()` hoặc `new Resource()` thay vì map mảng (array) thủ công (inline array). Tuyệt đối không map tay array cho các object quan hệ để tránh rò rỉ dữ liệu (data leakage) và đảm bảo tính thống nhất.

**EAGER LOADING & AVOIDING N+1**:
- Luôn đảm bảo eager load (`with()`) trong Controller/Service trước khi truyền vào Resource.
- Đọc kỹ `/.agent/rules/resource-mapping.md` trước khi tạo Resource để biết cách sử dụng các "Thin Resource" (Resource nhỏ gọn) cho relationships nhằm tối ưu performance và tránh N+1.

```php
// ❌ WRONG: Map array inline (collection or single)
'services' => $this->whenLoaded('services', function() {
    return $this->services->map(fn($item) => ['id' => $item->id, 'name' => $item->name]);
}),
'user' => $this->whenLoaded('user', function() {
    return ['id' => $this->user->id, 'name' => $this->user->name];
}),

// ✅ CORRECT: Tạo Resource riêng và sử dụng (Nên cân nhắc dùng SimpleResource nếu không cần full data)
'services' => ServiceCategorySimpleResource::collection($this->whenLoaded('services')),
'user' => new UserSimpleResource($this->whenLoaded('user')),
'profile' => new ProfileResource($this->whenLoaded('profile')),
```

### Dùng Constants trong Resource
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

---

## 8. Controller Structure

### Thin controllers
```php
class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create($request->validated());
        return Response::created(['order' => new OrderResource($order)]);
    }

    public function index(IndexOrderRequest $request): JsonResponse
    {
        $orders = $this->orderService->list($request->validated());
        return Response::pagination(
            OrderResource::collection($orders),
            $orders->total(),
            $orders->currentPage(),
            $orders->perPage()
        );
    }
}
```

---

## 9. File Organization

### Khi tạo feature mới
```
1. Controller  → Controllers/{Module}/{Entity}Controller.php
2. Service     → Services/{Module}/{Entity}Service.php
3. Request     → Requests/{Module}/{Entity}/Store{Entity}Request.php
4. Resource    → Resources/{Module}/{Entity}/{Entity}Resource.php
5. Constants   → Constants/Master/Models/{Entity}/*.php
6. Route       → routes/api/{module}.php
```

---

## 10. Naming Conventions

| Element | Pattern | Example |
|---------|---------|---------|
| Controller | `{Entity}Controller` | `OrderController` |
| Service | `{Entity}Service` | `OrderService` |
| Request | `{Action}{Entity}Request` | `StoreOrderRequest` |
| Resource | `{Entity}Resource` | `OrderResource` |
| Criteria | `{Description}Criteria` | `ActiveUserCriteria` |
| Const | `{Entity}{Type}Const` | `OrderStatusConst` |

---

---

## 11. Security (CRITICAL)

### IDOR Prevention
```php
// ❌ WRONG
$order = Order::find($id);

// ✅ CORRECT: Scope by owner
$order = $this->orderRepository->findWhere([
    'id' => $id,
    'user_id' => auth()->id()
]);

// ✅ CORRECT: Use Policy
$this->authorize('view', $order);
```

### Data Leakage Prevention
```php
// ❌ WRONG: Return Model directly (exposes secrets)
return Response::success($user);

// ✅ CORRECT: Always use Resource
return Response::success(new UserResource($user));
```

### Rate Limiting & Auth
- All public APIs MUST use `throttle` middleware.
- Internal limits: `auth:api` middleware implied.

---

## 12. Authentication

### JWT patterns
```php
// Login
$token = Auth::guard(CommonConst::API)->attempt($credentials);

// Get current user code
$userCode = auth()->user()->code;

// Logout
JWTAuth::parseToken()->invalidate(true);
```

---

## 13. Quick Checklist

Trước khi submit code, check:

- [ ] Dùng `Response::` facade thay vì `response()->json()`
- [ ] Extends đúng `BaseModel` hoặc `BaseMasterModel`
- [ ] Dùng Constants thay vì magic strings
- [ ] Dùng `RequestTrait` trong FormRequest
- [ ] Dùng Repository pattern (nếu có)
- [ ] Service extends `AbstractService` cho transactions
- [ ] Resource dùng Constants để map fields
- [ ] Đặt file đúng folder theo module
- [ ] Naming đúng convention
- [ ] Security: IDOR check & Data Leakage prevention
