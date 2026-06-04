# TECHNICAL DESIGN: HỆ THỐNG QUẢN LÝ TÀI LIỆU VÀ HỒ SƠ LƯU TRỮ

## 1. Thiết kế Cơ sở dữ liệu

### 1.1. Bảng `t_documents`
*   Lưu các tài nguyên tệp đính kèm.
*   Hỗ trợ liên kết đa hình qua `documentable_id` và `documentable_type`.
*   Tích hợp trực tiếp các cột khóa ngoại `employee_id`, `contract_id` và `transaction_id` để tăng tốc độ truy vấn đối chiếu.

---

## 2. Bản đồ File triển khai

```text
src/
├── app/
│   ├── Models/
│   │   └── Document.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── Document/
│   │   │           └── DocumentController.php
│   │   ├── Requests/
│   │   │   └── Document/
│   │   │       └── UploadDocumentRequest.php
│   │   └── Resources/
│   │       └── Document/
│   │           └── DocumentResource.php
│   ├── Services/
│   │   └── Api/
│   │       └── Document/
│   │           └── DocumentService.php
└── routes/
    └── api/
        └── document.php
```
