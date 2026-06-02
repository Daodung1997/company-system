# Resource Mapping Strategy & Registry

> **MANDATORY**: Mọi thao tác xử lý API Response thông qua `JsonResource` phải tuân thủ tài liệu này để đảm bảo hiệu suất hệ thống (tránh lỗi N+1 query), ngăn chặn rò rỉ dữ liệu (data leakage) và đảm bảo tính tái sử dụng.

## 1. Nguyên Tắc Cốt Lõi (Core Principles)

### 1.1. Tuyệt đối KHÔNG map tay mảng (No Manual Array Mapping)
- Khi trả về dữ liệu của một quan hệ (relationships như `belongsTo`, `hasOne`, `hasMany`), **KHÔNG BAO GIỜ** được tự ý tạo một mảng (array) nội tuyến.
- ❌ **Sai**: `'worker' => ['id' => $this->worker->id, 'name' => $this->worker->name]`
- ✅ **Đúng**: `'worker' => new WorkerSimpleResource($this->whenLoaded('worker'))`

### 1.2. Chiến Lược Thin Resources (Resource Nhỏ Gọn)
- Khi một Resource chính (Ví dụ: `UserResource` hoặc `JobResource`) chứa quá nhiều thông tin chi tiết hoặc chứa các quan hệ lồng nhau, việc tái sử dụng toàn bộ Resource đó vào bên trong một Resource khác sẽ gây phình to payload API và dễ dẫn đến truy vấn N+1.
- **Giải pháp**: Luôn cân nhắc tạo các **"Thin Resource"** (Thường có hậu tố `*SimpleResource` hoặc `*CompactResource`). Thin Resource chỉ map các trường cơ bản (vd: `id`, `name`, `avatar`, `code`) và **TUYỆT ĐỐI KHÔNG** gọi thêm (eager load) bất kỳ relations nào bên trong nó.

### 1.3. Eager Loading Bắt Buộc (Prevent N+1)
- Trước khi truyền dữ liệu vào Resource có chứa relationships, tầng Service/Repository **PHẢI** gọi `->with(['relation'])` (Eager Loading).
- Bên trong Resource, luôn sử dụng `$this->whenLoaded('relation_name')` thay vì truy cập trực tiếp `$this->relation_name` (vì truy cập trực tiếp sẽ kích hoạt lazy loading dẫn đến N+1).

---

## 2. Resource Mapping Registry (Tham khảo & Cập nhật liên tục)

Dưới đây là danh sách các mapping chuẩn mực. Bất cứ khi nào tạo ra một Thin Resource mới, AI Agent phải cập nhật vào danh sách này.

| Entity | Standard Resource (Chi tiết) | Thin Resource (Dùng cho quan hệ/List) |
|---|---|---|
| **User/Customer/Worker** | `App\Http\Resources\User\UserResource` | `App\Http\Resources\User\UserSimpleResource` |
| **Service Category** | `App\Http\Resources\ServiceCategory\ServiceCategoryResource` | `App\Http\Resources\ServiceCategory\ServiceCategorySimpleResource` |
| **Area** | `App\Http\Resources\Area\AreaResource` | `App\Http\Resources\Area\AreaSimpleResource` |
| **Quotation** | `App\Http\Resources\User\Quotation\QuotationResource` | `App\Http\Resources\User\Quotation\QuotationWorkerResource` |
| **Job** | `App\Http\Resources\User\Job\JobResource` (Customer) / `WorkerJobResource` | `App\Http\Resources\User\Job\JobSimpleResource` |
| **Image/Media** | N/A | `App\Http\Resources\Common\ImageSimpleResource` |

---

## 3. Quy Trình Làm Việc (Workflow Rule)

1. **Trước khi Code Resource**: Agent bắt buộc phải đọc file này.
2. **Khi Code**: Kiểm tra xem relations cần trả về bao nhiêu data. Nếu chỉ cần `id` và `name`, hãy check xem có Thin Resource trong bảng Registry chưa.
   - Nếu chưa có: Tạo mới Thin Resource (Vd: `php artisan make:resource UserSimpleResource`) -> Map các trường cơ bản -> Quay lại file này cập nhật vào bảng Registry.
   - Nếu đã có: Tái sử dụng ngay lập tức.
3. **Khi Review**: Agent thực hiện `/code-review` sẽ quét toàn bộ code, nếu thấy map mảng thủ công hoặc thiếu `whenLoaded`, sẽ đánh dấu là vi phạm nghiêm trọng (Blocker).
