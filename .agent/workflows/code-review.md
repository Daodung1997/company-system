---
description: 🔍 Chạy Code Review kiểm tra Convention, Security, Constants trước khi Commit
---

# WORKFLOW: /code-review

> **Trigger**: Khi User gõ `/code-review` hoặc yêu cầu:
> - "review code giúp mình"
> - "kiểm tra lại code trước khi commit"
> - "check convention và security"

**Mục tiêu**: Kích hoạt skill `code-reviewer` để tự động rà soát lại toàn bộ mã nguồn đã thay đổi, đảm bảo tuân thủ nghiêm ngặt Convention, Security, và các quy tắc đặc thù của dự án trước khi đẩy lên Git.

---

## 1. Context & Scope
**Action**:
1. Đọc hướng dẫn chuẩn tại `.agent/skills/code-reviewer/SKILL.md`.
2. Xác định các file đang cần review (thường là các file Agent vừa code hoặc thông qua `git status`/`git diff`).

---

## 2. Review Checklist (Các Trọng Tâm Cần Quét)

**Action**: Agent phải tự động kiểm tra mã nguồn theo các tiêu chuẩn sau:

### 2.1. Quy tắc Constants (Strict Use Statement)
- **Luôn import (use Class)** thay vì gọi Fully Qualified Namespace inline.
  - ❌ *Sai*: `$status === \App\Constants\Master\Models\Job\JobStatusConst::REFUNDED`
  - ✅ *Đúng*: 
    ```php
    use App\Constants\Master\Models\Job\JobStatusConst;
    ...
    if ($status === JobStatusConst::REFUNDED)
    ```

### 2.2. Quy tắc Resource (Reusable & Consistent)
- **Tuyệt đối KHÔNG map tay array cho các object quan hệ (relationships)** trong các class Resource. Phải tái sử dụng Resource của Entity tương ứng.
  - ❌ *Sai*: `'worker' => ['id' => $this->worker->id, 'name' => $this->worker->name]`
  - ✅ *Đúng*: `'worker' => $this->worker ? new UserResource($this->worker) : null`
- **Quét kỹ**: Khi review các file `*Resource.php`, phải rà soát xem có đoạn mã nào khởi tạo mảng `[]` hoặc array map thủ công cho quan hệ `belongsTo` / `hasOne` không. Nếu có, báo lỗi Blocks.

### 2.3. Quy tắc Security & Validation
- **IDOR Check**: Thao tác Edit/Delete/Show của User bắt buộc phải có check điều kiện sở hữu (vd: `user_id = auth()->id()`).
- **Data Leakage**: Tuyệt đối không trả model thô từ Controller. Phải đi qua `Resource`.
- **Validation**: Đảm bảo Request kế thừa `BaseRequest` và sử dụng `RequestTrait` để chuẩn hóa Response format lỗi.

### 2.4. Quy tắc Clean Code & Layering
- **Controller**: Siêu mỏng. Dùng Facade `Response::success/failure/pagination`. KHÔNG chứa Logic.
- **Service**: Extends `AbstractService`. Wrap Write/Update/Delete trong `beginTransaction()` / `commitTransaction()`. Ném `BusinessException`.
- **Repository**: Tách biệt truy vấn DB. Phải dùng `Criteria` Pattern cho List/Search endpoints.

---

## 3. Auto-Fix (Thực Thi Sửa Lỗi)

**Action**: Nếu phát hiện code vi phạm bất cứ điều gì trong danh sách trên:
1. Agent **tự động sửa** bằng các tool `replace_file_content` hoặc `multi_replace_file_content` mà không cần đợi User yêu cầu.
2. Gom các statement `use` lên đầu file, xoá bỏ namespace inline rác rưởi.

---

## 4. Review Report (Báo Cáo)

**Action**: Sau khi review và tự động sửa xong, Agent xuất một Markdown Report tóm tắt:
- Các file đã scan.
- Các vi phạm đã được tự động fix (vd: đã thêm lệnh `use` cho `JobStatusConst`).
- Lời khuyên/Cảnh báo bảo mật nếu có rủi ro tiềm tàng.

Xác nhận với User code đã **Ready to Commit**!
