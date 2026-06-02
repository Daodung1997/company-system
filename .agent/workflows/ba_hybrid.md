---
description: BA Hybrid: chạy Discovery app-level (docs/app/*), sau đó chọn module để tự động drill-down Delivery (docs/features/<slug>/*).
---

---
description: BA Hybrid: chạy Discovery docs/app/*, rồi drill-down Delivery docs/features/<slug>/* cho 1–3 module đã chọn.
---

# WORKFLOW: /ba_hybrid

1. Chạy Discovery (App-level)
   - Mục tiêu: tạo docs/app/*, KHÔNG đi sâu API spec/sequence/DB.
   - Quy tắc chống phình:
     - Tổng UC 15–30, mỗi module 6–12
   - Output required:
     - docs/app/00_app_context.md
     - docs/app/01_module_map.md
     - docs/app/02_app_usecase_overview.md
     - docs/app/03_api_inventory.md
     - docs/app/04_glossary.md
     - docs/app/05_delivery_plan.md
     - docs/app/decision-log.md

2. (Optional) Tạo thư mục output
   // turbo
   - Run:
     ```bash
     mkdir -p docs/app
     ```

3. Render 7 file Discovery theo templates/app/*
   - Tạo/ghi đè theo đúng template.
   - Thiếu thông tin: ghi [ASSUMPTION]/[OPEN] vào decision-log.

4. Chọn module để drill-down
   - Nếu user đã chỉ module (1–3 cái): dùng luôn.
   - Nếu user chưa chỉ:
     - Đưa shortlist 3–5 module ưu tiên (core first) và yêu cầu user chọn 1–3 module.

5. Với mỗi module được chọn: chạy Delivery pipeline rút gọn trước (B1+B2)
   - Tạo slug cho module.
   - Output:
     - docs/features/<slug>/00_usecase_overview.md
     - docs/features/<slug>/00_usecase_overview.md
     - docs/features/<slug>/01_apis/index.md

6. Với mỗi module: tiếp tục Delivery đầy đủ (B3→B5) theo batch
   - B3: tạo functional_spec.md cho top 5 API quan trọng nhất trước, list API còn lại để batch sau.
   - B4: tạo sequence cho top 5 API.
   - B5: tạo orm_design + schema_mapping + decision-log.

7. Kết thúc bằng “Progress summary”
   - Bảng theo module:
     - module | slug | #UC | #API | spec done | sequence done | db done | open items
   - Gợi ý next module theo docs/app/05_delivery_plan.md.