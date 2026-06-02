# Debug Failed Unit Tests

> Trigger: `/debug-test [test file or method]`

## Step 1: Chay lai Test Case

1. Xac dinh file test va method dang fail
2. Chay phpunit de tai hien loi:
   ```bash
   php artisan test --filter {TestMethod} {path/to/test}
   ```
3. Phan tich: Error output, Stack trace, Expected vs Actual

## Step 2: Doi chieu Specification

1. Xac dinh module lien quan
2. Doc file spec tai: `docs/features/<module>/01_apis/`
3. So sanh logic trong Spec vs logic trong code/test hien tai

## Step 3: Nghien cuu & Sua loi

Luon dam bao rules trong CLAUDE.md (coding conventions):

- **Spec dung, Code sai** -> Sua API Code
- **Spec dung, Test sai** -> Sua Test Case
- **Code dung, Spec thieu/sai** -> Bao lai User de quyet dinh

## Step 4: Verification

1. Chay lai test case de verify fix:
   ```bash
   php artisan test --filter {TestMethod}
   ```
2. Chay toan bo file test de dam bao khong regression:
   ```bash
   php artisan test --filter {TestClassName}
   ```
3. Bao cao: root cause + fix
