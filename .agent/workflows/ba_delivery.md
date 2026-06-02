---
description: BA Delivery (Module/Feature): tạo docs/features/<slug> theo pipeline UseCase → API → Functional Spec → Sequence → DB/ORM Laravel.
---

---
description: BA Delivery (Module/Feature): tạo docs/features/<slug> theo pipeline UseCase → API Decompose → API Spec → Sequence → DB/ORM Laravel.
---

# WORKFLOW: /ba_delivery

1. Pre-check Target
   - Yêu cầu user cung cấp:
     - module/feature name (1 cái), và nếu có: spec/mockup/json/schema liên quan.
   - Nếu user đã chạy Discovery: cho phép tham chiếu docs/app/01_module_map.md + docs/app/03_api_inventory.md.

2. Chuẩn hoá slug + phạm vi
   - Slug: kebab-case (vd: user-management, deck-recipe, billing).
   - Nếu module quá lớn:
     - Tự tách 3–7 sub-feature trước (ghi trong decision-log), rồi mới generate API specs hàng loạt.

3. (Optional) Tạo thư mục output
   // turbo
   - Run:
     ```bash
     mkdir -p docs/features/<slug>/01_apis docs/features/<slug>/02_sequences docs/features/<slug>/03_db
     ```

4. Step B1 — Use Case Overview (module/feature)
   - Create/overwrite theo templates/feature:
     - docs/features/<slug>/00_usecase_overview.md
     - docs/features/<slug>/00_usecase_overview.md
   - Rules:
     - 6–12 use cases/module (nếu vượt: đề xuất tách sub-feature).

5. Step B2 — API Decomposition
   - Create/overwrite:
     - docs/features/<slug>/01_apis/index.md (templates/feature/01_apis_index.md)
   - Rules:
     - Mỗi UC map ra 1+ API.
     - Nếu chưa có endpoint: đề xuất REST-style + auth yes/no.
     - API naming: <verb>_<resource> (vd: list_decks, create_deck, favorite_deck).

6. Step B3 — Functional Spec per API
   - Với mỗi API trong index, tạo:
     - docs/features/<slug>/01_apis/<module>/<api>/functional_spec.md (templates/feature/functional_spec.md)
   - Bắt buộc có:
     - permissions, validation, error codes, flow, data mapping (API ↔ DB/Derived), request/response examples.
   - Nếu số API quá nhiều:
     - Viết trước top 5 API quan trọng nhất + list phần còn lại (để batch tiếp).

7. Step B4 — Sequence Diagram per API
   - Tạo sequence cho top 5 API trước:
     - docs/features/<slug>/02_sequences/<api>.md (templates/feature/sequence_api.md)
   - Mỗi API: happy path + ít nhất 1 alt/error path quan trọng.
   - Lane chuẩn: Actor → UI/Client → Controller → Service → DB → External (nếu có).

8. Step B5 — DB + ORM Laravel
   - Create/overwrite:
     - docs/features/<slug>/03_db/orm_design.md (templates/feature/orm_design.md)
     - docs/features/<slug>/03_db/schema_mapping.md (templates/feature/schema_mapping.md)
     - docs/features/<slug>/decision-log.md (templates/feature/decision-log.md)
   - Rules:
     - Laravel conventions (snake_case table, singular Model, timestamps, softDeletes nếu cần).
     - Rõ FK/index/unique.
     - Không chắc type/cast: ghi [ASSUMPTION] + options.

9. Kết thúc bằng “Status board”
   - Bảng: API | Spec done | Sequence done | DB mapping done | Open items.
   - Nêu batch tiếp theo (nếu còn API chưa viết).