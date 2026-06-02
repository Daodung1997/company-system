# Changelog

## [2026-02-24]
### Added
- Thêm trường `block_reason` vào bảng `m_users` phục vụ tính năng lưu lý do khi Block Khách Hàng (Customer). Có thể tái sử dụng cho các Role User khác sau này.
- Thêm `ToggleCustomerStatusRequest` hỗ trợ validate dữ liệu mở/khóa khách hàng.
- Thêm `GenderConst.php` để chuẩn hóa kiểu dữ liệu Giới tính cho String (API) và Integer (Database).

### Changed
- Cập nhật API GET `/api/admin/customers/{id}` trả về `gender`, `birthday` (ISO8601), `avatar_code`, và bảng lưu vết trạng thái khoá `block_reason`.
- Cập nhật API PUT `/api/admin/customers/{id}` nhận thêm các trường `gender`, `dob`, `avatar_code`.
- Đồng bộ hóa Validation logic sử dụng Enum `UserRoleConst` làm chuẩn (kiểu String), loại bỏ `CommonRolesConst` gây sai lệch dữ liệu.

### Fixed
- Lỗi Data Type Mismatch ở Auth/Login và Admin Services gây ra bởi Int và String Role.
- Bug "Attempt to read property 'birthday' on null" khi Customer chưa có `CustomerProfile` trong Database.
- Bug "Incorrect integer value: 'female' for column 'gender'" (Đã fix nhờ GenderConst).
