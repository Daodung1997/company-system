# PROPOSAL: HỆ THỐNG QUẢN LÝ THU CHI VÀ CHI PHÍ (CASH FLOW & EXPENSES)

## 1. Yêu cầu nghiệp vụ
Hệ thống cần quản lý dòng tiền vào và ra (thu từ khách hàng, chi phí hoạt động, lương nhân sự) để kiểm soát tài chính của tổ chức và phục vụ công tác khai báo thuế.

## 2. Giải pháp kỹ thuật

### 2.1. Quản lý dòng tiền
*   **Thuế suất & Tự động tính toán**: Phân loại các giao dịch theo thuế suất (VAT 10%, 8%, 0%, Miễn thuế) và tự động tính toán số tiền trước thuế, số tiền thuế dựa trên tổng tiền giao dịch đã nhập.
*   **Hóa đơn hợp chuẩn (Qualified Invoices)**: Hỗ trợ ghi nhận mã đăng ký hóa đơn hợp chuẩn của Nhật Bản (`invoice_registration_number`) nhằm tuân thủ quy định mới về thuế.

### 2.2. Kiểm tra tuân thủ chéo
*   **Đối chiếu MST**: Hỗ trợ đối chiếu mã số thuế (`tax_code`) của doanh nghiệp từ cấu hình công ty (`CompanySetting`) để kiểm tra tính hợp pháp của các giao dịch chi phí lớn.
*   **Đính kèm chứng từ**: Yêu cầu bắt buộc đính kèm hóa đơn scan (EDM) đối với các khoản chi tiêu có giá trị lớn hơn hạn mức cấu hình.
