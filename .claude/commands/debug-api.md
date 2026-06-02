# Debug API Failure

> Trigger: `/debug-api [endpoint or error description]`
> Systematic approach de fix API failures.

## Step 1: Reproduce & Observe

1. Xac dinh endpoint, method, payload
2. Neu user chua cung cap: hoi ro endpoint + payload
3. Chay test hoac curl de tai hien loi
4. Ghi nhan: HTTP status, response body, error message

## Step 2: Trace & Expose (Iterative)

### 2.1. Check Route
```bash
php artisan route:list | grep [endpoint]
```
- Route co ton tai?
- Middleware dung?
- Controller method dung?

### 2.2. Check Request Validation
- Doc FormRequest rules
- Payload co thoa man rules?
- Check `messages()` method

### 2.3. Check Controller -> Service Flow
- Controller co goi dung Service method?
- Service co throw exception dung?
- Transaction co bi rollback?

### 2.4. Check Repository & Database
- Query co dung?
- Data co ton tai trong DB?
- Relationships co load dung?

## Step 3: Database Error Protocol (MANDATORY STOP)

Neu gap database error:
1. STOP - Khong sua migration/schema ngay
2. Doc migration file hien tai
3. So sanh voi actual DB schema: `php artisan schema:dump`
4. Xac dinh root cause truoc khi sua

## Step 4: Logic Error Protocol

1. Trace tu Controller -> Service -> Repository
2. Them log tam (se xoa sau) o cac diem nghi van
3. Chay lai de thu thap thong tin
4. Fix root cause, khong fix symptom

## Step 5: Verification

1. Chay lai test/request - confirm fix
2. Chay toan bo test file de dam bao khong regression:
   ```bash
   php artisan test --filter {TestClass}
   ```
3. Xoa log tam (neu co)
4. Bao cao: root cause + fix applied
