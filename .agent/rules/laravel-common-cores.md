# Reusable Core Components

> **Reference Documentation** cho các components dùng chung trong `src/app/`.
> Dùng tài liệu này để tránh code lại những thứ đã có (DRY).

---

## 1. Helper & Utils

### 🛠 `App\Utils\Helper`
Chứa các function static utility chung.

### 📄 `App\Supports\Facades\Response\Response`
Facade chuẩn hóa response trả về client. **BẮT BUỘC DÙNG**.

```php
use App\Supports\Facades\Response\Response;

// Success (200)
Response::success(['id' => 1]);

// Created (201)
Response::created(['id' => 1]);

// Error (Custom status)
Response::failure(['message' => 'error.code'], 400);

// Pagination
Response::pagination($collection, $total, $page, $limit);

// Resource Conflict (409)
Response::resourceConflict($data, $actions);
```

---

## 2. Constants System

Hệ thống Constants được tổ chức phân cấp rõ ràng trong `App\Constants`.

### 📂 `App\Constants\Commons`
Chứa constants dùng chung cho toàn hệ thống.
- **CommonConst**: `API` guard, `BEARER` token type.
- **CommonStatusConst**: `ACTIVE`, `INACTIVE`, `DELETED`.
- **CommonLocaleConst**: Locales supported.
- **CommonTokenConst**: JWT token keys (`ACCESS_TOKEN`, `EXPIRES_IN`).

### 📂 `App\Constants\Master`
Chứa constants cho Business Domain, group theo Entity.
- **Cấu trúc**: `App\Constants\Master\Models\{Entity}\...`
- **{Entity}Column**: Tên các cột trong DB (e.g. `UserColumn::EMAIL`).
- **{Entity}Relation**: Tên các relation model (e.g. `UserRelation::ROLES`).
- **Resource**: `App\Constants\Master\Resource\{Entity}ResourceConst` (Keys trả về trong JSON).

---

## 3. Base Models & Traits

### 🧱 `App\Models\BaseModel`
Class cha cho các Business Entity (Order, Wallet...).
- **Audit Trails**: Tự động fill `created_by`, `updated_by` từ `auth()->user()->code`.
- **Scopes**: `available()` (not deleted), `active()` (available + active status).

### 🧱 `App\Models\BaseMasterModel`
Class cha cho Master Data (User, Category...).
- extends `BaseModel`.
- **Auto Code Gen**: Tự động sinh `code` theo format `PREFIX + Padded ID` (e.g. `USR00001`).
- **Config**: Cần define `PREFIX_CODE` và `MAX_LENGTH_CODE`.

### 🧬 `App\Traits\RequestTrait`
Trait xử lý validation messages tự động.
- **renderMessageFromRule($rules)**: Tự động map rule name sang message key format `{field}.{rule}`.

---

## 4. Repository & Services

### 💾 `App\Repositories\Repository`
Base Repository implement Criteria pattern.
- **Metods**: `all`, `paginate`, `find`, `create`, `update`, `delete`.
- **Criteria**: `pushCriteria(new MyCriteria())` để filter query dynamic.
- **Chainable**: `$repo->available()->with(['relation'])->find($id)`.

### ⚙️ `App\Services\AbstractService`
Class cha cho Service Layer.
- **Transaction Helpers**: 
    - `beginTransaction()`
    - `commitTransaction()`
    - `rollbackTransaction()`

### 🔐 `App\Services\Common\BaseAuthService`
Nền tảng cho Authentication Service.
- Xử lý cơ bản về guard, login attempts.

---

## 5. Security & Middleware

### 🛡 `App\Http\Middleware\VerifyJWTToken`
Middleware xác thực JWT Token. Sử dụng `tymon/jwt-auth`.

### 🛡 `App\Http\Middleware\PermissionMiddleware`
Check permission của user hiện tại.

### 🛡 `App\Http\Middleware\LocaleMiddleware`
Set application locale dựa trên header request.

---

## 6. How to use Config & Enums

- **Config**: Sử dụng `config('key')`, không dùng `env()`.
- **Enums**: File cấu hình constants cũ đang được chuyển đổi dần sang native PHP Enums hoặc giữ dạng Interface Constants như hiện tại. Uu tiên tái sử dụng `CommonStatusConst` cho trạng thái phổ biến.

---

## 7. Image & File Handling

### 🖼 `App\Services\Common\UploadService`
Service chuyên biệt xử lý upload ảnh và file.
- **Features**: Upload, Resize (via `ResizeImageJob`), Optimize, Storage management.
- **Flow**:
    1. Client upload ảnh qua API `UploadController`.
    2. Server lưu ảnh, generate `code` (e.g. `IMG0001`), lưu vào `t_images`.
    3. Server trả về `code` và `url`.
    4. Client dùng `code` để attach vào các entity khác (e.g. `ServiceCategory.icon_code`).

### 🧱 `App\Models\Image`
Model quản lý metadata của file/ảnh.
- **Table**: `t_images`
- **Prefix**: `IMG`
- **Method**: `getUrl()` để lấy full url của ảnh.

### 📝 Usage Pattern
Khi implement entity có ảnh (Avatar, Icon...):
1. DB: Lưu column `{field}_code` (varchar 20), index.
2. Model:
    ```php
     public function icon() {
        return $this->hasOne(Image::class, 'code', 'icon_code');
    }
    ```
3. Resource:
    ```php
    'icon_url' => $this->icon ? $this->icon->getUrl() : null,
    ```

