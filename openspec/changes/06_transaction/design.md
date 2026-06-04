# TECHNICAL DESIGN: HỆ THỐNG QUẢN LÝ THU CHI VÀ CHI PHÍ

## 1. Thiết kế Cơ sở dữ liệu

### 1.1. Bảng `t_transactions`
*   Lưu các giao dịch tài chính thu chi.
*   Lưu thông tin thuế VAT, thuế khấu trừ tại nguồn và mã hóa đơn hợp chuẩn.

---

## 2. Bản đồ File triển khai

```text
src/
├── app/
│   ├── Models/
│   │   └── Transaction.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── Transaction/
│   │   │           └── TransactionController.php
│   │   ├── Requests/
│   │   │   └── Transaction/
│   │   │       └── StoreTransactionRequest.php
│   │   └── Resources/
│   │       └── Transaction/
│   │           └── TransactionResource.php
│   ├── Services/
│   │   └── Api/
│   │       └── Transaction/
│   │           └── TransactionService.php
└── routes/
    └── api/
        └── transaction.php
```
