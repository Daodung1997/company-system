# ViecVat AI Backend - OpenSpec Workflow

Repo này dùng OpenSpec làm lớp quản lý change/spec trước khi code. Tài liệu ổn định cho team vẫn nằm trong `docs/app`, `docs/features`, và `docs/origins`; OpenSpec chỉ là workspace để tạo, review, implement, và archive từng thay đổi.

## Nguyên tắc chính

- Tài liệu OpenSpec và phần trả lời liên quan đến docs/spec phải viết bằng tiếng Việt.
- Giữ nguyên technical identifiers: path, class name, API field, JSON key, error code, table name, enum value.
- Với OpenSpec spec, giữ các keyword bắt buộc như `ADDED Requirements`, `Requirement`, `Scenario`, `WHEN`, `THEN`, `SHALL`, `MUST` để validate/archive đúng.
- Không code trực tiếp từ chat rời rạc. Luôn đi qua proposal/spec/design/tasks trước khi implement.
- Khi viết spec API, dùng `ba-doc-writer` và đọc nguồn từ `docs/origins` nếu có.
- Khi implement BE, dùng `feature-implementer`.
- Sau khi implement API, chạy `docs-sync` để `docs/features` khớp code thật.

## Cấu hình OpenSpec

OpenSpec đã được cài cho Antigravity:

```text
.agent/skills/openspec-*
.agent/workflows/opsx-*
openspec/config.yaml
openspec/schemas/viec-vat-sdd/
```

Schema mặc định của repo là `viec-vat-sdd`, được custom cho workflow ViecVat:

- Spec/docs bằng tiếng Việt.
- Task tách riêng BE, FE Vue3, FE Flutter.
- BE route sang `feature-implementer`.
- Spec route sang `ba-doc-writer`.
- Cuối flow có `docs-sync` và verification.

## Flow anh cần thực hiện

### 1. Bắt đầu một change

Anh mô tả yêu cầu bằng một lệnh:

```text
/opsx:propose <mô tả change>
```

Ví dụ:

```text
/opsx:propose thêm API cấu hình phí nền tảng theo từng khu vực
```

AI sẽ tự tạo workspace:

```text
openspec/changes/<change-name>/
```

Và sinh các file:

- `proposal.md`: lý do, phạm vi, ảnh hưởng.
- `specs/<capability>/spec.md`: behavior contract.
- `design.md`: quyết định thiết kế, docs sync plan, task routing.
- `tasks.md`: checklist task.

### 2. Review spec và task

AI tự viết `tasks.md`, anh không cần tự phân task thủ công.

Anh chỉ review lại:

- Scope có đúng chưa.
- API/validation/response có đúng yêu cầu chưa.
- Task BE, Vue3, Flutter có tách đúng chưa.
- Có phần nào chưa làm thì yêu cầu AI sửa trước khi apply.

Ví dụ yêu cầu sửa:

```text
Sửa tasks.md: change này chưa cần Flutter, chỉ cần BE và Vue3.
```

Hoặc:

```text
Bổ sung docs sync cho module payment sau khi implement.
```

### 3. Viết stable docs bằng ba-doc-writer

Trước khi code, AI dùng `ba-doc-writer` để tạo/cập nhật tài liệu ổn định:

```text
docs/features/<module>/00_usecase_overview.md
docs/features/<module>/01_apis/<api>/functional_spec.md
docs/features/<module>/01_apis/<api>/02_sequence.md
docs/features/<module>/03_entity_reference.md
```

Nếu là thay đổi app-level, cập nhật thêm:

```text
docs/app/01_module_map.md
docs/app/02_app_usecase_overview.md
docs/app/03_api_inventory.md
docs/app/04_database_design/
```

Nguồn ưu tiên:

```text
docs/origins/
docs/app/
docs/features/
ticket/mockup/yêu cầu trong chat
code hiện tại
```

Nếu thiếu thông tin, ghi `[ASSUMPTION]` thay vì tự khẳng định.

### 4. Implement change

Khi anh đã ok spec và task, chạy:

```text
/opsx:apply <change-name>
```

AI sẽ đọc `proposal.md`, `spec.md`, `design.md`, `tasks.md`, rồi làm theo checklist.

Với BE Laravel, AI tự áp dụng `feature-implementer` theo task đã generate. Anh không cần chạy thêm lệnh riêng cho `feature-implementer`, trừ khi muốn nhấn mạnh:

```text
/opsx:apply <change-name>
Khi làm BE, bắt buộc dùng feature-implementer.
```

Với FE:

- Vue3: làm trong repo `../viec-vat-ai-fe`.
- Flutter: làm trong repo `../viec_vat_flutter`.

Nếu đang mở backend repo mà task cần FE, AI sẽ báo cần chuyển workspace/repo tương ứng hoặc hướng dẫn phần cần làm ở repo FE.

### 5. Verify và docs sync

Sau implement, AI cần chạy hoặc đề xuất lệnh verify phù hợp:

```bash
cd src
php artisan test --filter=<TestNameOrModule>
```

Sau đó dùng `docs-sync` cho module/API bị ảnh hưởng để kiểm tra:

- Method/path/middleware trong routes.
- FormRequest validation rules.
- Resource response fields.
- Response envelope.
- Business errors và error codes.
- Permission/auth behavior.

Mục tiêu: `docs/features` phải khớp code thật để FE và QA dùng được.

### 6. Archive change

Khi code xong, test pass, docs sync xong:

```text
/opsx:archive <change-name>
```

OpenSpec sẽ archive behavior contract vào `openspec/specs`. Stable docs vẫn nằm ở `docs/features` và `docs/app`.

## Khi nào dùng lệnh nào

| Lệnh | Khi dùng |
|------|----------|
| `/opsx:propose <mô tả>` | Bắt đầu change mới, để AI tạo proposal/spec/design/tasks |
| `/opsx:apply <change>` | Implement theo task đã được review |
| `/opsx:archive <change>` | Đóng change sau khi code/test/docs sync xong |
| `/implement` | Legacy workflow, chỉ dùng khi muốn implement trực tiếp từ docs cũ |
| `/api-test` | Legacy workflow để tạo test riêng |
| `/debug-test` | Debug test fail |
| `/code-review` | Review code trước khi merge |

## Source of Truth

| Loại tài liệu | Vai trò |
|---------------|---------|
| `docs/origins` | Nguồn yêu cầu gốc, constraint gốc |
| `docs/app` | Bức tranh app-level: module map, use case, API inventory, DB design |
| `docs/features` | API contract/stable docs cho BE, FE, QA |
| `openspec/changes/<change>` | Workspace tạm cho change đang làm |
| `openspec/specs` | Behavior catalog sau khi archive |

Khi OpenSpec và stable docs lệch nhau, đọc lại nguồn và code thật, sau đó cập nhật artifact bị stale. Không giữ hai version mâu thuẫn.

## Backend conventions cần giữ

- Architecture: Controller -> Service -> Repository/Criteria -> Model.
- Response facade: `App\Supports\Facades\Response\Response`.
- Success response: `{"code": HTTP_CODE, "data": {...}}`.
- Validation error: `{"code": 422, "messages": ["field.rule"]}`.
- Business error: `{"code": 4xx, "messages": {"message": "...", "error_code": "..."}}`.
- DB naming: `m_` cho master data, `t_` cho transaction data.
- Feature tests bắt buộc cho API mới hoặc API thay đổi.

## Cấu trúc liên quan

```text
openspec/
├── config.yaml
├── changes/
├── specs/
└── schemas/
    └── viec-vat-sdd/

docs/
├── app/
├── features/
└── origins/

.agent/
├── rules/
├── skills/
└── workflows/

src/
├── app/
├── routes/
├── database/
└── tests/
```
# company-system
