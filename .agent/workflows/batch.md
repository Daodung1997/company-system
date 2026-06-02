---
description: 🔄 Chia task lớn thành batch và tự động gọi Gemini CLI thực thi
---

# WORKFLOW: /batch

> **Trigger**: Khi User gõ `/batch [task description]` hoặc các cụm từ:
> - "chia batch để implement [module]"
> - "batch implement [feature]"
> - "thực hiện song song [modules]"
> - "chia nhỏ task [description]"

**Mục tiêu**: Phân tích task lớn → Chia thành các batch độc lập → Tạo plan → Sau khi User approve, tự động gọi Gemini CLI để thực thi từng batch.

---

## 1. Load Skill

**Action**: Đọc skill hướng dẫn.
1. Đọc: `.agent/skills/batch-orchestrator/SKILL.md`
2. Đọc: `.agent/rules/coding-conventions.md` (context cho prompt template)

---

## 2. Phân tích & Phân rã Task

**Action**: Hiểu yêu cầu và chia batch.

1. **Đọc docs/specs** liên quan đến task
2. **Xác định các đơn vị công việc** (APIs, modules, features, migrations)
3. **Vẽ Dependency Graph** (PlantUML):
   - Node nào có thể chạy song song?
   - Node nào phải chạy tuần tự?
4. **Nhóm thành batches** theo quy tắc:
   - Mỗi batch **không trùng file** với batch khác
   - Mỗi batch **có thể verify** độc lập
   - Batch size: 1-3 units/batch

---

## 3. Tạo Batch Plan

**Action**: Tạo `implementation_plan.md` theo format:

```markdown
# [Task] - Batch Execution Plan

## Dependency Graph
@startuml
[Batch 1: Infrastructure] --> [Batch 2: Feature A]
[Batch 1: Infrastructure] --> [Batch 3: Feature B]
[Batch 2: Feature A] --> [Batch 4: Tests]
[Batch 3: Feature B] --> [Batch 4: Tests]
@enduml

## Batch Summary
| # | Name | Depends On | Mode | Est. Time |
|---|------|-----------|------|-----------|
| 1 | Infrastructure | None | Sequential | 5m |
| 2 | Feature A | Batch 1 | Parallel | 10m |
| 3 | Feature B | Batch 1 | Parallel | 10m |
| 4 | Tests | Batch 2, 3 | Sequential | 8m |

## Batch Details
### Batch 1: [Name]
- **Scope**: ...
- **Files**: ...
- **Verification**: ...
- **Gemini CLI Prompt**: (Full prompt text)
```

**STOP** → Xin User approve.

---

## 4. Thực thi Batches (Sau khi User approve)

### 4.1. Skip Git Checkpoint
- (Bỏ qua git stash theo yêu cầu, tiếp tục chạy trực tiếp từ nhánh hiện tại)

### 4.2. Tạo Prompt Files
- Tạo thư mục: `mkdir -p .agent/batch-prompts`
- Mỗi batch tạo 1 file: `.agent/batch-prompts/batch-{N}-{name}.txt`
- Nội dung = Full prompt đã approve trong plan

### 4.3. Chạy Batches

#### Sequential Batches:
```bash
bash .agent/skills/batch-orchestrator/scripts/run-batch.sh {N} {name} .agent/batch-prompts/batch-{N}-{name}.txt yolo
```
- Chờ hoàn thành → Verify → Tiếp batch kế

#### Parallel Batches:
**QUAN TRỌNG:** KHÔNG DÙNG `run-parallel.sh`. Thay vào đó, Agent (Antigravity) phải tự gọi tool `run_command` nhiều lần một lúc (concurrent function calls) cho từng batch:
```bash
# Agent thực thi song song N tool calls (mỗi call gọi run-batch.sh cho 1 file)
bash .agent/skills/batch-orchestrator/scripts/run-batch.sh {N} {name} .agent/batch-prompts/batch-{N}-{name}.txt yolo
```
- **GIỚI HẠN CONCURRENCY:** Chỉ được chạy tối đa **3 batch cùng lúc** để tránh tràn RAM/CPU gây crash. Nếu có từ 4 batch trở lên, phải chia nhỏ (chạy 3 quá trình -> đợi xong -> chạy tiếp các batch còn lại).
- Việc gọi `run_command` cho mỗi batch sẽ mở một Terminal Tab riêng trên UI Antigravity, cho phép luồng thực thi Gemini CLI hiển thị realtime giúp User dễ dàng verify tiến trình.
- Chỉ gộp (join) hoặc verify sau khi tất cả các terminal này báo DONE.
- Check log: `cat .agent/logs/batch-{N}-*.log`
- **Nếu batch fail**: Phân tích log. 
  - Đứt gãy code, build error: Dừng pipeline → Tổng hợp lỗi → Sửa prompt → Re-run.
  - Conflict task/file (do batch khác đang can thiệp mảng chung): Chờ batch đang song song kia chạy xong → Tự điều phối chạy lại batch fail.

---

## 5. Verification

1. **Per-batch**: Check logs, chạy tests riêng
2. **Full suite**: `php artisan test` (sau khi tất cả batch xong)
3. **Conflict check**: Đảm bảo không có file bị ghi đè sai

---

## 6. Theo Dõi và Dọn Dẹp Sự Kiện (Tự động)

- Khi `run-batch.sh` chạy thành công (Exit Code 0), script sẽ **tự động xóa các file task-{N}.txt và batch-{N}.txt** tương ứng trong `.agent/batch-prompts/` để chống đầy bộ nhớ và ngăn chặn nhầm lẫn tài nguyên ở batch kế tiếp.
- `run-batch.sh` sẽ tự động sinh report append vào file **`.agent/batch-report.md`**.
- Agent (Antigravity) cần đọc file report này và liên tục cập nhật checkmark ở `task.md`.

---

## ⚠️ Safety Rules
- **KHÔNG dùng Git Checkpoint**: Tuyệt đối không `git stash`, tiếp tục chạy từ trạng thái hiện tại.
- **KHÔNG BAO GIỜ** cho parallel batch sửa cùng 1 file.
- **Tự động Approval**: Default dùng tham số `yolo` (`--approval-mode yolo`) ở mọi script.
- **Nếu fail**: Tổng hợp log lại và dừng tiến trình. NẾU lỗi fail là do conflict hệ thống hoặc file từ một task khác đang chạy song song, AI phải tự điều phối: Chờ batch đó chạy thành công, merge và sau đó tự retry lại batch lỗi.
