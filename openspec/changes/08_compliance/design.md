# TECHNICAL DESIGN: ĐỘNG CƠ KIỂM SOÁT TUÂN THỦ

## 1. Thiết kế Cơ sở dữ liệu

### 1.1. Bảng `t_compliance_issues`
*   Lưu trữ các sự cố tuân thủ phát hiện trong hệ thống.
*   Các khóa ngoại `employee_id`, `contract_id` và `transaction_id` để liên kết chéo trực tiếp đến nguồn phát sinh sự cố.

---

## 2. Bản đồ File triển khai

```text
src/
├── app/
│   ├── Models/
│   │   └── ComplianceIssue.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── Compliance/
│   │   │           └── ComplianceController.php
│   │   ├── Requests/
│   │   │   └── Compliance/
│   │   │       └── ResolveComplianceRequest.php
│   │   └── Resources/
│   │       └── Compliance/
│   │           └── ComplianceIssueResource.php
│   ├── Services/
│   │   └── Api/
│   │       └── Compliance/
│   │           └── ComplianceService.php
└── routes/
    └── api/
        └── compliance.php
```
