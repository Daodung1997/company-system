# TECHNICAL DESIGN: HỆ THỐNG QUẢN LÝ HỢP ĐỒNG

## 1. Thiết kế Cơ sở dữ liệu

### 1.1. Bảng `contracts`
*   Lưu trữ thông tin chi tiết của hợp đồng.
*   Khóa ngoại `employee_id` (nếu là HĐ lao động) và `company_id`.
*   Tích hợp các trường phục vụ thỏa thuận làm việc và làm thêm giờ (36 Agreement).

---

## 2. Bản đồ File triển khai

```text
src/
├── app/
│   ├── Models/
│   │   └── Contract.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── Contract/
│   │   │           └── ContractController.php
│   │   ├── Requests/
│   │   │   └── Contract/
│   │   │       └── StoreContractRequest.php
│   │   └── Resources/
│   │       └── Contract/
│   │           └── ContractResource.php
│   ├── Services/
│   │   └── Api/
│   │       └── Contract/
│   │           └── ContractService.php
└── routes/
    └── api/
        └── contract.php
```
