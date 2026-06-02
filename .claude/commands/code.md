# Viet Code theo Spec hoac Agile Mode

> Trigger: `/code [description]`
> Code dung, code sach, code an toan.

## Giai doan 1: Context Awareness

### 1.1. Check Spec
- Co file Spec trong `docs/features/<module>` ?
  - **CO**: Che do **Strict Implementation** (Code theo Spec)
  - **KHONG**: Che do **Agile Coding** (Code nhanh)

### 1.2. Agile Coding Mode
- Phan tich yeu cau User
- Tu vach "Mini-Plan" (3-4 buoc)
- Xin confirm: "Se sua file A, tao file B. OK khong?"

## Giai doan 2: Hidden Requirements (Tu dong them)

### 2.1. Input Validation
- Email dung format? Phone hop le? So khong am? String khong qua dai?

### 2.2. Error Handling
- Moi API call phai co try-catch
- Moi database query phai handle loi
- Error message than thien (khong lo thong tin ky thuat)
- Dung BusinessException voi ExceptionCode

### 2.3. Security
- SQL Injection: Dung parameterized queries
- XSS: Escape output
- CSRF: Token cho form
- Auth Check: Moi API sensitive phai check quyen
- IDOR: Scope data by owner

### 2.4. Performance
- Pagination cho danh sach dai
- Eager loading de tranh N+1

## Giai doan 3: Implementation

- Tach logic ra services
- Khong de logic phuc tap trong Controller
- Dat ten bien/ham ro rang
- Follow coding conventions trong CLAUDE.md

## Giai doan 4: Quality Check (Tu dong)

- Doi chieu voi yeu cau ban dau
- Cover edge cases
- Tu review code vua viet
- Check code smell, potential bugs
- Chay test: `php artisan test --filter {TestClass}`

## Giai doan 5: Handover

1. Bao cao: "Da code xong [Ten Task]"
2. Liet ke: "Cac file da thay doi: [Danh sach]"
3. Goi y next steps
