# FUNCTIONAL SPECIFICATION: HỆ THỐNG XÁC THỰC (AUTHENTICATION CONTRACT)

## 1. API: Đăng nhập nhân sự (`POST /api/auth/login`)
Cho phép Nhân sự (Employee) đăng nhập vào hệ thống bằng Email hoặc Số điện thoại cùng mật khẩu.

*   **URL:** `/api/auth/login`
*   **Method:** `POST`
*   **Headers:**
    *   `Content-Type: application/json`
    *   `Accept: application/json`
    *   `X-Locale: vi` (hoặc `ja` / `en`)

### Request Body Schema
```json
{
  "username": "string | required | email hoặc phone hợp lệ",
  "password": "string | required | min:6"
}
```

### Response Schema (Success 200)
```json
{
  "code": 200,
  "data": {
    "access_token": "string (JWT Token)",
    "token_type": "bearer",
    "expires_in": 3600,
    "employee": {
      "id": "integer",
      "code": "string (EMPXXXX)",
      "full_name": "string",
      "email": "string",
      "phone": "string",
      "role": "string",
      "company": {
        "id": "integer",
        "name": "string",
        "corporate_number": "string"
      }
    }
  }
}
```

### Error Responses
*   **422 Unprocessable Entity (Lỗi Validation):**
    ```json
    {
      "code": 422,
      "messages": {
        "username": ["Trường tài khoản đăng nhập là bắt buộc."],
        "password": ["Mật khẩu phải chứa ít nhất 6 ký tự."]
      }
    }
    ```
*   **401 Unauthorized (Sai tài khoản/mật khẩu):**
    ```json
    {
      "code": 401,
      "messages": {
        "message": "Tài khoản hoặc mật khẩu không chính xác.",
        "error_code": "AUTH_INVALID_CREDENTIALS"
      }
    }
    ```

---

## 2. API: Đăng xuất nhân sự (`POST /api/auth/logout`)
Vô hiệu hóa Token JWT hiện tại của nhân sự đang đăng nhập.

*   **URL:** `/api/auth/logout`
*   **Method:** `POST`
*   **Headers:**
    *   `Authorization: Bearer <token>`
    *   `Accept: application/json`

### Response Schema (Success 200)
```json
{
  "code": 200,
  "data": {
    "message": "Đăng xuất thành công."
  }
}
```

---

## 3. API: Xem hồ sơ cá nhân (`GET /api/auth/me`)
Lấy thông tin chi tiết của nhân sự đang đăng nhập dựa trên token.

*   **URL:** `/api/auth/me`
*   **Method:** `GET`
*   **Headers:**
    *   `Authorization: Bearer <token>`
    *   `Accept: application/json`

### Response Schema (Success 200)
```json
{
  "code": 200,
  "data": {
    "id": "integer",
    "code": "string",
    "full_name": "string",
    "full_name_kana": "string | null",
    "romaji_name": "string | null",
    "email": "string",
    "phone": "string",
    "identity_type": "string",
    "identity_number": "string | null",
    "tax_code": "string | null",
    "social_insurance_code": "string | null",
    "join_date": "string (YYYY-MM-DD)",
    "status": "string",
    "company": {
      "id": "integer",
      "name": "string",
      "tax_code": "string"
    },
    "department": {
      "id": "integer",
      "name": "string"
    }
  }
}
```

---

## 4. API: Làm mới Token (`POST /api/auth/refresh-token`)
Sinh lại một JWT Token mới khi token cũ gần hết hạn.

*   **URL:** `/api/auth/refresh-token`
*   **Method:** `POST`
*   **Auth Required:** Không (nhưng cần truyền JWT token cũ qua Header `Authorization`)

### Response Schema (Success 200)
```json
{
  "code": 200,
  "data": {
    "access_token": "string (JWT Token mới)",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

---

## 5. API: Quên mật khẩu (`POST /api/auth/forgot-password`)
Gửi mã OTP 6 chữ số đến email của nhân sự để xác thực trước khi đặt lại mật khẩu. Mã OTP có hiệu lực trong 10 phút.

*   **URL:** `/api/auth/forgot-password`
*   **Method:** `POST`
*   **Auth Required:** Không

### Request Body Schema
```json
{
  "email": "string | required | email hợp lệ | max:150"
}
```

### Response Schema (Success 200)
```json
{
  "code": 200,
  "data": {
    "message": "Mã xác thực đã được gửi đến email của bạn.",
    "debug_token": "049378"
  }
}
```
> **Lưu ý:** Trường `debug_token` chỉ hiển thị ở môi trường `local` và `testing` để phục vụ kiểm thử. Trong môi trường production, mã OTP chỉ được gửi qua email và KHÔNG trả về trong response.

### Error Responses
*   **404 Not Found (Email không tồn tại):**
    ```json
    {
      "code": 404,
      "messages": {
        "message": "Không tìm thấy tài khoản với email này.",
        "error_code": "USER_NOT_FOUND"
      }
    }
    ```
*   **429 Too Many Requests (Gửi quá nhiều lần):**
    ```json
    {
      "code": 429,
      "messages": {
        "message": "Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau 1 phút.",
        "error_code": "PWD_003"
      }
    }
    ```
*   **403 Forbidden (Tài khoản bị khóa):**
    ```json
    {
      "code": 403,
      "messages": {
        "message": "Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.",
        "error_code": "ACCOUNT_DISABLED"
      }
    }
    ```

### Quy tắc nghiệp vụ (Business Rules)
1.  Giới hạn tối đa **5 yêu cầu/phút** (cấu hình qua `OTP_FORGOT_PASSWORD_PER_MINUTE` trong `.env`).
2.  Khi gửi yêu cầu mới, tất cả các token cũ của nhân sự đó sẽ bị xóa (chỉ giữ token mới nhất).
3.  Mã OTP được mã hóa (hash) trước khi lưu vào DB để đảm bảo bảo mật.
4.  Mã OTP hết hạn sau 10 phút kể từ thời điểm sinh.

---

## 6. API: Đặt lại mật khẩu (`POST /api/auth/reset-password`)
Xác thực mã OTP và đặt mật khẩu mới cho nhân sự. Sử dụng kết hợp với API `forgot-password`.

*   **URL:** `/api/auth/reset-password`
*   **Method:** `POST`
*   **Auth Required:** Không

### Request Body Schema
```json
{
  "email": "string | required | email hợp lệ | max:150",
  "token": "string | required | chính xác 6 ký tự (mã OTP)",
  "password": "string | required | min:8 | max:50 | chữ hoa + chữ thường + chữ số + ký tự đặc biệt",
  "password_confirmation": "string | required | khớp với password"
}
```

### Response Schema (Success 200)
```json
{
  "code": 200,
  "data": {
    "message": "Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập lại."
  }
}
```

### Error Responses
*   **400 Bad Request (Mã OTP không chính xác):**
    ```json
    {
      "code": 400,
      "messages": {
        "message": "Mã xác thực không chính xác.",
        "error_code": "INVALID_OTP"
      }
    }
    ```
*   **400 Bad Request (Mã OTP hết hạn):**
    ```json
    {
      "code": 400,
      "messages": {
        "message": "Mã xác thực đã hết hạn. Vui lòng gửi lại yêu cầu quên mật khẩu.",
        "error_code": "TOKEN_EXPIRED"
      }
    }
    ```
*   **400 Bad Request (Nhập sai OTP quá nhiều lần):**
    ```json
    {
      "code": 400,
      "messages": {
        "message": "Bạn đã nhập sai mã xác thực quá nhiều lần. Vui lòng gửi lại yêu cầu quên mật khẩu.",
        "error_code": "OTP_003"
      }
    }
    ```

### Quy tắc nghiệp vụ (Business Rules)
1.  Giới hạn tối đa **5 lần nhập sai OTP** (cấu hình qua `OTP_MAX_WRONG_ATTEMPTS` trong `.env`). Sau khi vượt quá, token bị xóa và nhân sự phải gửi yêu cầu quên mật khẩu lại.
2.  Mỗi lần nhập sai OTP, cột `attempts` tăng thêm 1 để theo dõi.
3.  Sau khi đặt lại mật khẩu thành công, token OTP bị xóa khỏi DB.
4.  Mật khẩu mới được tự động hash trước khi lưu vào DB (thông qua Eloquent `hashed` cast).

---

## 7. API: Đổi mật khẩu (`POST /api/auth/change-password`)
Nhân sự đang đăng nhập tự đổi mật khẩu của mình. Yêu cầu xác minh mật khẩu hiện tại trước khi cho phép thay đổi.

*   **URL:** `/api/auth/change-password`
*   **Method:** `POST`
*   **Auth Required:** Có (Header `Authorization: Bearer <token>`)

### Request Body Schema
```json
{
  "current_password": "string | required | min:6",
  "password": "string | required | min:8 | max:50 | chữ hoa + chữ thường + chữ số + ký tự đặc biệt",
  "password_confirmation": "string | required | khớp với password"
}
```

### Response Schema (Success 200)
```json
{
  "code": 200,
  "data": {
    "message": "Mật khẩu đã được thay đổi thành công.",
    "access_token": "string (JWT Token mới)",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```
> **Lưu ý:** Sau khi đổi mật khẩu thành công, JWT token cũ bị vô hiệu hóa ngay lập tức. Hệ thống tự động sinh token mới và trả về để Frontend cập nhật mà không cần đăng nhập lại.

### Error Responses
*   **400 Bad Request (Mật khẩu hiện tại sai):**
    ```json
    {
      "code": 400,
      "messages": {
        "message": "Mật khẩu hiện tại không chính xác.",
        "error_code": "CURRENT_PASSWORD_NOT_MATCH"
      }
    }
    ```
*   **422 Unprocessable Entity (Lỗi Validation):**
    ```json
    {
      "code": 422,
      "messages": {
        "password": ["Mật khẩu xác nhận không khớp."]
      }
    }
    ```
