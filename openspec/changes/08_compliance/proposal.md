# PROPOSAL: ĐỘNG CƠ KIỂM SOÁT TUÂN THỦ (COMPLIANCE ENGINE)

## 1. Yêu cầu nghiệp vụ
Hệ thống cần tự động hóa việc phát hiện các rủi ro tuân thủ về lao động và tài chính (như làm thêm giờ quá giới hạn cho phép, giao dịch thiếu hóa đơn, thông tin cấu hình pháp lý doanh nghiệp bị bỏ trống) để giảm thiểu rủi ro pháp lý cho doanh nghiệp.

## 2. Giải pháp kỹ thuật

### 2.1. Động cơ phân tích luật lệ (Rule Engine)
*   **Quét tự động & thủ công**: Hệ thống hỗ trợ lập lịch quét định kỳ hàng tháng hoặc cho phép người quản trị click nút quét thủ công qua API `/api/compliance/scan`.
*   **Xử lý luật lệ**:
    1.  *Luật chấm công*: Quét giờ OT của nhân viên và đối chiếu với hạn mức trong hợp đồng lao động và quy định chung.
    2.  *Luật tài chính*: Kiểm tra các giao dịch chi tiêu không có mã số thuế đối tác hoặc thiếu tệp scan hóa đơn đính kèm.
    3.  *Luật pháp nhân*: Kiểm tra tính đầy đủ thông tin pháp lý (Mã số thuế, địa chỉ, đại diện pháp luật, con dấu doanh nghiệp) trong cài đặt công ty (`CompanySetting`).

### 2.2. Ghi nhận sự cố & Phê duyệt giải quyết
*   **Trạng thái sự cố**: Các lỗi được phát hiện sẽ tự động lưu vào bảng `t_compliance_issues` ở trạng thái `DETECTED`.
*   **Giải quyết**: Quản trị viên sau khi xử lý thực tế có thể cập nhật trạng thái sự cố sang `RESOLVED` kèm ghi chú giải trình.
