---
description: BA Discovery (App-level): tạo docs/app/* gồm context, module map, use case overview, api inventory, glossary, delivery plan.
---

---
description: BA Discovery (App-level): tạo docs/app/* (context, module map, use case diagram, API inventory, glossary, delivery plan, decision log).
---

# WORKFLOW: /ba_discovery

1. Pre-check Input
   - Nếu user đã cung cấp spec/sitemap/menu/API list: dùng ngay.
   - Nếu chưa có: yêu cầu user paste ít nhất 1 trong 3:
     (a) sitemap/menu, hoặc (b) danh sách module, hoặc (c) API list hiện có.

2. Chuẩn hoá phạm vi Discovery
   - System boundary: toàn app.
   - Actors: User/Admin/System/External (nếu có).
   - Constraints: timezone mặc định Asia/Bangkok; auth/multi-tenant/audit nếu chưa rõ thì ghi [OPEN].
   - Quy tắc chống phình:
     - Tổng use cases toàn app: 15–30
     - Mỗi module: 6–12 use cases
     - Không viết chi tiết API functional spec / sequence / DB/ORM ở workflow này
     - Thiếu thông tin: ghi [ASSUMPTION] và [OPEN], không hỏi lại ngay

3. (Optional) Tạo thư mục output
   // turbo
   - Run:
     ```bash
     mkdir -p docs/app
     ```

4. Tạo `docs/app/00_app_context.md` theo template
   - Dùng templates/app/00_app_context.md.
   - Điền: Summary, Actors, Scope, External Systems, Constraints, Core Data.
   - Ghi [ASSUMPTION]/[OPEN] vào đúng section.

5. Tạo `docs/app/01_module_map.md` theo template
   - Dùng templates/app/01_module_map.md.
   - Liệt kê module theo domain (Core/Admin/Integration/Reporting…).
   - Điền dependency và cross-cutting (RBAC, notification, upload, report…).

6. Tạo `docs/app/02_app_usecase_overview.md` theo template
   - Dùng templates/app/02_app_usecase_overview.md.
   - Tạo packages theo module; UC đặt `UC-xx: <name>` + 1-line goal trong label.
   - Giữ giới hạn UC theo quy tắc chống phình.

7. Tạo `docs/app/03_api_inventory.md` theo template
   - Dùng templates/app/03_api_inventory.md.
   - Mỗi module có bảng endpoint draft:
     - UC Ref, Endpoint, Method, Purpose, Auth, Notes
   - Nếu chưa có endpoint: đề xuất REST-style, chưa cần payload/response chi tiết.

8. Tạo `docs/app/04_glossary.md` theo template
   - Dùng templates/app/04_glossary.md.
   - Ưu tiên thuật ngữ business; thêm Related Module.

9. Tạo `docs/app/05_delivery_plan.md` theo template
   - Dùng templates/app/05_delivery_plan.md.
   - Đề xuất thứ tự triển khai “Core first” + dependency + risks + exit criteria.

10. Tạo `docs/app/decision-log.md` theo template
    - Dùng templates/app/decision-log.md.
    - Tổng hợp [ASSUMPTION-*] và [OPEN-*] từ toàn bộ discovery.

11. Kết thúc bằng “Module shortlist” để drill-down
    - Tóm tắt 5–10 module quan trọng + dependency chính.
    - Đề xuất 1 module ưu tiên nhất để chạy Delivery tiếp.
    - Hướng dẫn user: chạy workflow `/ba_delivery` và paste module name + spec liên quan.