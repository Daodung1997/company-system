# PROPOSAL: HỆ THỐNG QUẢN LÝ HỢP ĐỒNG (CONTRACT MANAGEMENT)

## 1. Yêu cầu nghiệp vụ
Hệ thống cần số hóa quy trình quản lý hợp đồng bao gồm hợp đồng lao động nhân sự và hợp đồng thương mại/đối tác. Đồng thời, kiểm soát các điều khoản pháp lý về làm thêm giờ (thỏa thuận 36 Agreement) và tự động đồng bộ hóa thông tin sang tính lương.

## 2. Giải pháp kỹ thuật

### 2.1. Đăng ký & Ký kết hợp đồng
*   **Tự điền thông tin (Auto-Fill)**: Khi tạo hợp đồng mới, thông tin doanh nghiệp đại diện Bên A (tên, mã số thuế, địa chỉ, người đại diện) sẽ được tự động điền từ cấu hình công ty (`CompanySetting`).
*   **Phân loại hợp đồng**: Phân chia biểu mẫu động giữa Hợp đồng lao động (`LABOR`) và Hợp đồng thương mại (`COMMERCIAL`, `VENDOR`).

### 2.2. Đóng dấu & Xuất bản PDF
*   **Tích hợp con dấu**: Tệp tin ảnh con dấu tròn của công ty (`hanko_seal_path`) trong cấu hình công ty được tự động in chèn vào phần ký tên Bên A trên file PDF khi người dùng thực hiện xuất hợp đồng.
*   **Chứng từ điện tử (EDM)**: Cho phép đính kèm tệp tin scan hợp đồng vật lý lưu trữ tập trung.
