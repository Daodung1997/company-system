---
name: stitch-designer
description: Sử dụng Google Stitch MCP để thiết kế và quản lý UI/UX màn hình ứng dụng từ requirements hoặc text prompt.
---

# Stitch Designer Skill

## 1. Mục đích
Cung cấp hướng dẫn tiêu chuẩn để Agent sử dụng **Google Stitch MCP** trong việc tạo các màn hình giao diện (UI) cho dự án, thay thế việc tạo ảnh tĩnh truyền thống. Stitch MCP giúp tạo ra các dự án thiết kế có thể tái sử dụng, chỉnh sửa, và hệ thống hoá cao.

---

## 2. Quy trình thiết kế với Stitch MCP

### Bước 1: Khởi tạo Project
- Mỗi cụm tính năng hoặc dự án mới nên có một Project tập trung.
- Sử dụng tool: `mcp_stitch_create_project`
- **Output cần lấy**: `projectId` (bỏ tiền tố `projects/`).

### Bước 2: Khai báo Design System (Tuỳ chọn nhưng cực kỳ khuyến nghị)
- Để đảm bảo tính đồng nhất (Consistency) về màu sắc, typography và phong cách thiết kế.
- Sử dụng tool: `mcp_stitch_create_design_system`
- Truyền vào `designSystem` các thông số như bảng màu gốc, định dạng bo góc, tuỳ chọn Dark/Light mode và `design_md` (hướng dẫn markdown chi tiết về phong cách).
- Nếu cần cập nhật sau này, dùng `mcp_stitch_update_design_system` (có thể cần link asset lại vào các màn hình qua `mcp_stitch_apply_design_system`).

### Bước 3: Phân tích Specs và soạn Prompt
- Đọc kỹ tài liệu nội dung màn hình (ví dụ: `docs/origins/app/auth/login.md`).
- **Cấu trúc Prompt chuẩn**:
  1. Loại màn hình & Tên nền tảng (VD: Mobile app Login screen).
  2. Concept tổng thể (VD: Dark theme, Premium, Teal aesthetic).
  3. Liệt kê từ trên xuống dưới (Top to Bottom):
     - App bar / Header.
     - Illustration (nếu có).
     - Component cụ thể kèm text (Input Email có icon gì, Button CTA màu gì, text ra sao).
     - Footer / Liên kết phụ trợ.
- **Lưu ý**: Có chứa các text Tiếng Việt cần thiết để giao diện trực quan nhất.

### Bước 4: Tạo các màn hình
- Phân phối các thao tác tạo màn hình qua `mcp_stitch_generate_screen_from_text`.
- Truyền `projectId`, `prompt`, `deviceType` (thường là `MOBILE`).
- **Quan trọng**: Do việc gen bằng mô hình AI tạo UI khá mất thời gian, hãy khuyến nghị agent tự động theo dõi hoặc báo trước cho User.

### Bước 5: Lấy kết quả & Iteration
- Sau quá trình tạo, dùng `mcp_stitch_list_screens` để kiểm tra toàn bộ thành phẩm.
- Khi User cần điều chỉnh một màn hình, dùng `mcp_stitch_edit_screens` truyền screenId và yêu cầu update.
- Nếu User cần thử nghiệm một vài layout khác, sử dụng `mcp_stitch_generate_variants`.

---

## 3. Tool Reference (Stitch MCP Tools)
- `mcp_stitch_create_project`
- `mcp_stitch_create_design_system` & `mcp_stitch_apply_design_system`
- `mcp_stitch_generate_screen_from_text`
- `mcp_stitch_edit_screens`
- `mcp_stitch_generate_variants`
- `mcp_stitch_list_projects` / `mcp_stitch_list_screens` / `mcp_stitch_get_screen`
- `mcp_stitch_list_design_systems`
