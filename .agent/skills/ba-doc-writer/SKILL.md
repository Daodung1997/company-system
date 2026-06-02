---
name: ba-doc-writer
description: Business analysis and specification writing in 2 layers: (A) Discovery/App-level to decompose modules + use case map + glossary + API inventory, then (B) Delivery pipeline for each module/feature: Use Case → decompose Function/API → Functional Spec (per API) → Sequence Diagram → DB/ORM Laravel design. Used for docs/spec requests from tickets, mockups, images, JSON, schema, or code.
---

# BA Docs Writer Skill (Antigravity)

## When to use this skill
Use when the user requests:
- "business analysis", "write spec/specification document", "mapping field", "write acceptance criteria/testcase"
- "draw use case / sequence diagram / UML (PlantUML)"
- "decompose API by function", "write spec for each API"
- "design DB, ORM Laravel/Eloquent, migration"
- has mockup/images, scattered descriptions, ticket backlog, JSON sample, or code snippets and needs standardization into docs
- input is **entire app** (multiple modules + multiple features) and needs **decomposition** to avoid bloating

## Objectives
1) Standardize scattered requirements → clear, structured documentation, ready for dev/test
2) Support **2 layers**: Discovery (app/module map) and Delivery (implementable docs)
3) Always adhere to user's requested format (template/language/output limits)
4) If information is missing, record **Assumptions** + **Open Questions** (only ask back when logical deduction is impossible)

## Acceptable Inputs
- Spec/ticket describing features or entire app (text)
- Mockup/screenshot (UI)
- Sitemap/Menu / module list (very useful for app-level)
- List of existing APIs (endpoint/payload/response) or "propose API" request
- DB schema/tables/mapping fields/JSON sample
- Business regulations (role, permission, workflow, SLA)
- Target technology (Laravel version, DB engine, naming convention) if user possesses

## Output Rules (Critically Important)
- If user provides "MANDATORY" template/format → ADHERE 100%
- If user requests "only return 1 markdown code block" → do exactly that
- Avoid verbose fluff; prioritize bullets, mapping tables, Given/When/Then
- Always distinct: Scope, Actors, UC, Rules, Flow, UI, Data, AC, Sequence, DB/ORM
- When speculating: clearly state `[ASSUMPTION] ...` and do not present as fact
- For diagrams: prioritize **PlantUML** (`.md`) for easy version control
- Prevent "document bloat":
  - App-level total Use Cases: ~15–30 (depending on app), per module: ~6–12 UC
  - Module/feature-level is where API/Sequence/DB details go

---

# Modes (Must Support)
- **Discovery Mode (App-level)**: decompose entire app → module map + use case map + glossary + preliminary API inventory
- **Delivery Mode (Module-level / Feature-level)**: implementable pipeline (Use Case → API → Spec → Sequence → DB/ORM)
- **Hybrid Mode**: Discovery first, then select 1–N modules to run Delivery (if user requests)

---

# Workflow A — Discovery (App-level / Scoping)
Use when input is **entire app** or spec is too long / mixed with multiple modules.

## Step A0 — Define Boundaries
- System boundary: App or just Module
- Actor list (User/Admin/System/3rd party)
- External systems (payment, SSO, webhook, queue, storage)

## Step A1 — Module Map
Minimum Output:
- Module table: `Module | Purpose | Primary Actors | Core Data | Dependencies | Notes`
- Group modules by domain (Core / Admin / Integration / Reporting)

## Step A2 — App Use Case Map (App Level)
Minimum Output:
- Use Case list in `UC-xx` format by module, 1-line description/UC
- Use Case diagram (PlantUML) with system boundary and actor mapping
- Limit: total UC ~15–30; per module ~6–12 UC (prioritize "covering the map")

## Step A3 — Glossary (Domain terms)
Minimum Output:
- Term list: `Term | Meaning | Source | Notes`
- Attach term to relevant module

## Step A4 — API Inventory (Preliminary)
Minimum Output:
- Endpoint list by module (if any), or proposed endpoint framework by UC
- Do not detail request/response at this step

## Step A5 — Delivery Plan (Roadmap)
Minimum Output:
- Order of module/feature implementation by dependency
- "Core first": Auth/Role/User → domain CRUD → workflow → report → integration

## Step A6 — Database Design (App-level, Full Schema)
Minimum Output:
- Full ERD diagram (PlantUML) with all entities & relationships
- Master Data vs Transaction Data classification
- Tables/columns + constraints + indexes + soft delete/timestamps
- Eloquent Models blueprint (fillable/casts/relations)
- Migration execution order (dependency-based)

**Naming Rules (MANDATORY):**
- **Master Data** (Rarely changed data, categories, config, entities): Prefix `m_` (e.g., `m_users`, `m_products`, `m_categories`)
- **Transaction Data** (Transaction data, logs, continuous changes): Prefix `t_` (e.g., `t_orders`, `t_transactions`, `t_logs`)

Files:
- `docs/app/04_database_design/00_overview.md` (Total ERD, entity list, naming conventions)
- `docs/app/04_database_design/01_master_data.md` (Master entities: users, categories, services...)
- `docs/app/04_database_design/02_transaction_data.md` (Transaction entities: orders, payments, logs...)
- `docs/app/04_database_design/03_orm_models.md` (Eloquent Models blueprint)
- `docs/app/04_database_design/04_migrations.md` (Migration skeleton & execution order)

### Proposed Files (Discovery)
- `docs/app/00_app_context.md`
- `docs/app/01_module_map.md`
- `docs/app/02_app_usecase_overview.md`
- `docs/app/03_api_inventory.md`
- `docs/app/04_database_design/` (Full DB design - see A6)
- `docs/app/05_glossary.md`
- `docs/app/06_delivery_plan.md`
- `docs/app/decision-log.md` (Assumptions/Open Questions total)

---

# Workflow B — Delivery (Module-level / Feature-level Pipeline)
Use when input is a specific module/feature, or after Discovery has selected a module to drill-down.

## Step B0 — Determine Mode & Output Scope
- If user says "only need Use Case" → only do Step B1
- If user says "only need individual API spec" → do Step B2–B3 (with simplified B1 if needed)
- If user says "only need Sequence" → do Step B4 based on API list
- If user says "only need DB/ORM Laravel" → do Step B5 (with minimal mapping)
- If user is unclear → run full pipeline Step B1→B5

## Step B1 — Overall Use Case (Overview) for Module/Feature
Minimum Output:
- Actor(s)
- Use Case list (UC-xx) + 1-line description
- System boundary + include/extend (if any)
Files:
- `docs/features/<slug>/00_usecase_overview.md`

## Step B2 — Decompose into Functions/APIs
Principles:
- Group by module/sub-feature
- Each Function/API includes: purpose, actor/trigger, main input/output, related data
Output:
- API decomposition tree
- Endpoint list (if not yet existed)
File:
- `docs/features/<slug>/01_apis/index.md`

## Step B3 — Write Functional Spec for each API (per functional_spec.md)
Create `functional_spec.md` for each API using the template file.
- **Template**: `@[templates/feature/functional_spec.md]`
- **Output**: `docs/features/<slug>/01_apis/<api>/functional_spec.md`

### Key Conventions (MANDATORY)
1. **Response format**: Luôn dùng `{"code": HTTP_CODE, "data": {...}}`. **KHÔNG dùng** `{"success": true/false}` hay `{"status": "success"}`.
2. **Validation errors (422)**: Message Key dạng `field.rule` (vd: `email.required`, `password.min`). **KHÔNG dùng** custom codes `VAL_001`.
3. **Business Rules & Validation**: Chia 2 bảng riêng:
   - **Validation Rules (422)**: columns `Rule ID | Field | Rule | Message Key`
   - **Business Rules (4xx)**: columns `Rule ID | Rule | HTTP | error_code`
4. **Origin Cross-reference (MANDATORY)**: Khi viết Validation Rules, **PHẢI** đọc `docs/origins/<module>/` tương ứng để lấy chính xác:
   - Max length (ký tự) — KHÔNG tự đặt giá trị, lấy từ spec gốc
   - Unique scope + case-sensitivity
   - File type/size constraints
   - Fields khác nhau theo entity level (vd: danh mục lớn vs nhỏ có fields khác nhau)
   - Nếu `docs/origins/` không có giá trị cụ thể → ghi `[ASSUMPTION]` kèm lý do chọn
   - Nếu phát hiện mâu thuẫn giữa origins và context khác → ghi `[ASSUMPTION]` kèm giá trị được chọn và lý do

### Response Standard Reference
> SOURCE OF TRUTH: `src/app/Supports/Components/Response/ResponseFormat.php`

| Type | Used by | Format |
|------|---------|--------|
| Success (200) | `Response::success($data)` | `{"code": 200, "data": {...}}` |
| Created (201) | `Response::created($data)` | `{"code": 201, "data": {...}}` |
| Pagination (200) | `Response::pagination(...)` | `{"code": 200, "data": {"data": [...], "total", "current_page", "limit", "metadata"}}` |
| Validation Error (422) | `ValidationException` | `{"code": 422, "messages": ["field.rule"]}` |
| Business Error (4xx) | `BusinessException` | `{"code": 4xx, "messages": {"message": "...", "error_code": "..."}}` |
| Not Found (404) | `Response::notFound()` | `{"code": 404, "messages": ["Resource not found"]}` |
| Generic Error (500) | `Response::failure()` | `{"code": 500, "messages": ["Error message"]}` |

## Step B4 — Sequence Diagram for each API
Minimum 1 diagram per API:
- Actor → Client/UI → Controller → Service → DB → External (if any)
- Happy path + at least 1 important error/alternative path
File:
- `docs/features/<slug>/01_apis/<api>/02_sequence.md`

## Step B5 — Entity Reference (Feature-level, DO NOT redefine)
> ⚠️ **IMPORTANT**: Full Database Design is located at `docs/app/04_database_design/` (Step A6).
> Feature-level only **references** relevant entities, DO NOT redefine schema.

Minimum Output:
- List of related entities (link to app-level)
- Field mapping: API field ↔ Entity.column
- If feature requires NEW entity not in app-level → add to `docs/app/04_database_design/`

File:
- `docs/features/<slug>/03_entity_reference.md`

### Template for `03_entity_reference.md`:
```markdown
# Entity Reference: <Feature Name>

## Related Entities
| Entity | Purpose in this feature | App-level Doc |
|--------|------------------------|---------------|
| User | Actor performing action | [01_master_data.md](../../app/04_database_design/01_master_data.md#user) |
| Order | Main transaction | [02_transaction_data.md](../../app/04_database_design/02_transaction_data.md#order) |

## Field Mapping (API ↔ DB)
| API Field | Entity.Column | Notes |
|-----------|---------------|-------|
| user_id | m_users.id | FK |
| status | t_orders.status | Enum: pending, completed |

## New Entities (if any)
> If this feature needs a new entity, add to `docs/app/04_database_design/` and reference here.
```

---

# Proposing Create File in Repo (Suggestion)
## If doing app-level (Discovery)
- `docs/app/*` as listed in Workflow A

## If doing module/feature-level (Delivery)
- `docs/features/<slug>/*` as listed in Workflow B

---

# Quick Modes (by intent)
- **Discovery Mode (App-level)**: Workflow A (A0→A6, including full DB design)
- **UseCase Mode (Module/Feature)**: B1
- **API Decompose Mode**: B2 (+ simplified B1 if needed)
- **API Spec Mode**: B3 (per functional_spec.md template)
- **Sequence Mode**: B4
- **Entity Reference Mode**: B5 (reference only, no redefinition)
- **Full Delivery Pipeline Mode**: B1→B5 (B5 only references entities)
- **Hybrid Mode**: A0→A6 then select module to run B1→B5
- **Database Design Mode**: A6 only (when needing to update/extend DB schema)

---

# Few-shot Examples

## Example 1 — Input is entire app
### Input
User: "Here is the spec for the whole app + sitemap/menu, please analyze the overall picture."

### Output (Discovery Mode)
- docs/app/00_app_context.md
- docs/app/01_module_map.md
- docs/app/02_app_usecase_overview.md
- docs/app/03_api_inventory.md
- docs/app/04_glossary.md
- docs/app/05_delivery_plan.md
- docs/app/decision-log.md

## Example 2 — Input is 1 module/feature
### Input
User: "From this spec, draw the overall use case, decompose each API, write functional_spec for each API, draw sequence, then design DB ORM laravel."

### Output (Full Delivery Pipeline Mode)
- 00_usecase_overview.(md/md)
- 01_apis/index.md + functional_spec.md for each API
- 02_sequences/*.md
- 03_db/orm_design.md + migrations/models skeleton
- decision-log.md (assumptions/open questions)
