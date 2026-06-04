# MODULE 4: QUẢN LÝ CHẤM CÔNG & NGHỈ PHÉP (TIMESHEETS & LEAVE REQUESTS)
## OpenSpec - Đặc tả chức năng chi tiết

---

## 1. Tổng quan
Module quản lý chấm công và đơn xin nghỉ phép nhằm theo dõi thời gian làm việc hàng ngày của nhân viên, quản lý ca làm việc, tính toán lương ban đầu (payroll statistics) và xét duyệt nghỉ phép.

### Đối tượng (Entities):
- **Timesheet** - Ghi nhận check-in/check-out hàng ngày của nhân viên.
- **LeaveRequest** - Đơn xin nghỉ phép (phép năm, ốm, thai sản...) và trạng thái phê duyệt.
- **WorkingHourConfig** - Cấu hình thời gian làm việc chuẩn (giờ bắt đầu, giờ kết thúc, giờ nghỉ trưa).
- **EmployeeShift** - Ghi nhận ca làm việc được gán cho nhân viên theo ngày.

---

## 2. Database Schema

### 2.1. Table `timesheets`
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `employee_id` | BIGINT UNSIGNED | FK → employees.id | Nhân viên |
| `date` | DATE | REQUIRED | Ngày chấm công (YYYY-MM-DD) |
| `check_in` | TIMESTAMP | NULLABLE | Thời gian vào ca |
| `check_out` | TIMESTAMP | NULLABLE | Thời gian ra ca |
| `timezone` | VARCHAR(50) | DEFAULT 'Asia/Ho_Chi_Minh' | Múi giờ |
| `status` | VARCHAR(20) | REQUIRED | Trạng thái (ON_TIME, LATE, EARLY_LEAVE, ABSENT) |
| `note` | TEXT | NULLABLE | Ghi chú điều chỉnh hoặc giải trình |

### 2.2. Table `leave_requests`
| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `employee_id` | BIGINT UNSIGNED | FK → employees.id | Nhân viên làm đơn |
| `leave_type` | VARCHAR(50) | REQUIRED | Loại nghỉ phép: ANNUAL, SICK, MATERNITY, UNPAID, OTHER |
| `leave_session` | VARCHAR(50) | REQUIRED | Buổi nghỉ: ALL_DAY, MORNING, AFTERNOON |
| `start_date` | DATE | REQUIRED | Ngày bắt đầu nghỉ |
| `end_date` | DATE | REQUIRED | Ngày kết thúc nghỉ |
| `reason` | TEXT | REQUIRED | Lý do xin nghỉ |
| `attachment_path` | VARCHAR(255) | NULLABLE | Minh chứng đính kèm (giấy viện, đơn...) |
| `status` | VARCHAR(20) | REQUIRED | Trạng thái phê duyệt (PENDING, APPROVED, REJECTED) |
| `approved_by` | BIGINT UNSIGNED | FK → employees.id, NULLABLE | Người duyệt |
| `approved_at` | TIMESTAMP | NULLABLE | Thời điểm duyệt |
| `approver_note` | TEXT | NULLABLE | Ghi chú từ người duyệt |

---

## 3. API Endpoints

### 3.1. API Chấm công (Timesheets)
| Method | URL | Auth | Mô tả |
|--------|-----|------|-------|
| GET | `/api/timesheets/monthly` | ✅ | Lấy bảng công cá nhân theo tháng |
| POST | `/api/timesheets/check-in` | ✅ | Thực hiện Check-in |
| POST | `/api/timesheets/check-out` | ✅ | Thực hiện Check-out |
| GET | `/api/timesheets/manage` | ✅ | Quản trị viên xem bảng công toàn nhân sự |
| GET | `/api/timesheets/statistics` | ✅ | Thống kê số ngày đi muộn, về sớm, nghỉ |
| GET | `/api/timesheets/payroll` | ✅ | Thống kê bảng lương dự tính |
| POST | `/api/timesheets/store-manual` | ✅ | Tạo mới chấm công thủ công (Admin/Manager) |

### 3.2. API Đơn nghỉ phép (Leave Requests)
| Method | URL | Auth | Mô tả |
|--------|-----|------|-------|
| GET | `/api/leave-requests` | ✅ | Lấy danh sách đơn nghỉ phép của bản thân |
| POST | `/api/leave-requests` | ✅ | Nộp đơn xin nghỉ phép mới |
| GET | `/api/leave-requests/pending` | ✅ | Lấy danh sách đơn xin nghỉ đang chờ phê duyệt |
| POST | `/api/leave-requests/{id}/approve` | ✅ | Phê duyệt hoặc từ chối đơn nghỉ phép |

---

## 4. Chi tiết API cốt lõi

### 4.1. GET /api/timesheets/monthly - Bảng công cá nhân
*   **Query Params:** `year_month=2026-06`
*   **Response (200):**
```json
{
  "code": 200,
  "data": [
    {
      "date": "2026-06-01",
      "check_in": "2026-06-01 08:55:00",
      "check_out": "2026-06-01 17:35:00",
      "status": "ON_TIME",
      "working_hours": 8.0,
      "overtime": 0.0
    }
  ]
}
```

### 4.2. POST /api/leave-requests - Nộp đơn nghỉ phép
*   **Request Body:**
```json
{
  "leave_type": "ANNUAL",
  "leave_session": "ALL_DAY",
  "start_date": "2026-06-05",
  "end_date": "2026-06-06",
  "reason": "Nghỉ phép đi du lịch gia đình",
  "attachment_path": ""
}
```
*   **Response (201):**
```json
{
  "code": 201,
  "data": {
    "id": 15,
    "employee_id": 3,
    "leave_type": "ANNUAL",
    "status": "PENDING",
    "start_date": "2026-06-05",
    "end_date": "2026-06-06"
  }
}
```

---

## 5. Mối liên hệ với Cấu hình Công ty (Company Settings Integration)
*   **Thương hiệu giao diện (Branding UI):** Sidebar hiển thị tên phụ (`sidebar_sub_name`) và logo công ty (`logo_path`) được truy xuất từ cấu hình công ty để tạo nhận diện thương hiệu cho nhân viên khi thực hiện chấm công và quản lý nghỉ phép.
*   **Thông báo & Phê duyệt:** Khi đơn nghỉ phép được duyệt, hệ thống sử dụng địa chỉ email doanh nghiệp (`email`) trong cấu hình công ty làm địa chỉ gửi thông báo chính thức nếu cấu hình gửi mail của hệ thống được kích hoạt.

