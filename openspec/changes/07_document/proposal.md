# PROPOSAL: HỆ THỐNG QUẢN LÝ TÀI LIỆU VÀ HỒ SƠ LƯU TRỮ (DOCUMENT ARCHIVE)

## 1. Yêu cầu nghiệp vụ
Hệ thống cần cung cấp một trung tâm lưu trữ và quản lý tệp đính kèm số hóa (EDM) nhằm phục vụ lưu vết các chứng từ pháp lý, bản scan hợp đồng và các cấu hình đồ họa (Logo, chữ ký, con dấu) của doanh nghiệp.

## 2. Giải pháp kỹ thuật

### 2.1. Đăng ký & Tải lên tập trung
*   **API Tải lên dùng chung**: Tạo endpoint `/api/documents/upload` chấp nhận các tệp đính kèm dạng hình ảnh hoặc văn bản, lưu trữ vật lý vào đĩa cứng (Local/S3) và ghi siêu dữ liệu (metadata) vào bảng `t_documents`.
*   **Liên kết Hồ sơ**: Cho phép gắn liên kết đa hình với các đối tượng cụ thể như: Nhân viên, Hợp đồng, Giao dịch tài chính hoặc Cấu hình Công ty.

### 2.2. Xem trước và Bảo mật
*   **Đường dẫn xem trước**: Trả về URL xem trước trực tuyến (dạng chỉ đọc) hoặc nút tải xuống tệp gốc tùy thuộc vào cấu hình bảo mật của tài nguyên đó.
