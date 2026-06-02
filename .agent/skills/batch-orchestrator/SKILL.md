---
name: batch-orchestrator
description: Breaks large tasks into independent batches and delegates each batch to a separate Gemini CLI process for autonomous execution.
---

# Batch Orchestrator Skill

> **Goal**: Decompose complex/large tasks into independent batches, then delegate each batch to a dedicated Gemini CLI process. Each batch runs autonomously with full context, following all project conventions.

---

## 1. 📐 When to Batch

A task SHOULD be batched when:
- It involves **3+ independent units of work** (e.g., multiple APIs, multiple modules, multiple migrations)
- Each unit is **self-contained** (no circular dependency between batches)
- Total estimated effort > **30 minutes** of AI coding time

A task SHOULD NOT be batched when:
- Units are tightly coupled (Batch B depends on output of Batch A)
- Task is small enough for a single session
- Task requires heavy back-and-forth with user

---

## 2. 📝 Planning Phase (Mandatory Before Execution)

### 2.1 Analyze & Decompose

1. **Read all relevant docs/specs** for the task
2. **Identify independent units** (APIs, features, modules, test suites)
3. **Map dependencies** between units
4. **Group into batches** following these rules:
   - Each batch MUST be independently executable
   - Each batch MUST produce a verifiable result (tests pass, file created, etc.)
   - Batch size: 1-3 related units per batch (sweet spot)
   - If Batch B depends on Batch A, they MUST be sequential (not parallel)

### 2.2 Create Batch Plan

Create `implementation_plan.md` with the following structure:

```markdown
# [Task Name] - Batch Execution Plan

## Overview
[Brief description of the full task]

## Dependency Graph
[PlantUML diagram showing batch dependencies]

## Batch Breakdown

### Batch 1: [Name] (Priority: P0)
- **Scope**: [What this batch covers]
- **Files**: [List of files to create/modify]
- **Depends on**: None (or Batch N)
- **Verification**: [How to verify this batch succeeded]
- **Prompt**: [Exact prompt that will be sent to Gemini CLI]

### Batch 2: [Name] (Priority: P0/P1)
...

## Execution Strategy
- **Mode**: Sequential / Parallel / Mixed
- **Estimated batches**: N
- **Estimated total time**: X minutes
```

### 2.3 User Review

- Present the batch plan to user
- **STOP and WAIT** for explicit approval
- User may:
  - Approve all batches → Execute all
  - Approve specific batches → Execute only those
  - Request changes → Update plan

---

## 3. 🚀 Execution Phase

### 3.0 Rules Enforcement Strategy (2 Layers)

Gemini CLI running in headless mode needs **explicit rules injection** to follow project conventions:

**Layer 1: Root `GEMINI.md`** (auto-read by Gemini CLI)
- File: `{PROJECT_ROOT}/GEMINI.md`
- Contains condensed architecture + key patterns quick reference
- Gemini CLI reads this automatically when running in the project directory
- Instructs Gemini CLI to read full rules from `.agent/rules/`

**Layer 2: `build-prompt.sh`** (injects full rules into each batch prompt)
- Script: `.agent/skills/batch-orchestrator/scripts/build-prompt.sh`
- **Physically embeds** the contents of `coding-conventions.md`, `laravel-common-cores.md`, and `definition-of-done.md` into each batch prompt file
- Guarantees Gemini CLI has ALL rules even if it fails to read external files
- Adds a compliance checklist at the end of every prompt

**Usage:**
```bash
# Step 1: Write a batch task file (just the TASK-specific content)
cat > /tmp/batch-task.txt << 'EOF'
## SCOPE
src/app/Services/Payment/PaymentService.php
src/app/Http/Controllers/Payment/PaymentController.php
...

## APIs to implement
- POST /api/payments/create-order
- POST /api/payments/callback

## TASK
1. Read docs/features/payment/01_apis/create-order/functional_spec.md
2. Implement PaymentService.createOrder() with VNPay integration
3. Create FormRequest with validation from spec
...

## VERIFICATION
php artisan test --filter PaymentTest
EOF

# Step 2: Build the full prompt with all rules injected
bash .agent/skills/batch-orchestrator/scripts/build-prompt.sh 2 payment-apis /tmp/batch-task.txt

# Step 3: Run via Gemini CLI
bash .agent/skills/batch-orchestrator/scripts/run-batch.sh 2 payment-apis .agent/batch-prompts/batch-2-payment-apis.txt auto_edit
```

### 3.1 Prompt Building (MANDATORY for every batch)

**ALWAYS use `build-prompt.sh`** to create batch prompts. NEVER write raw prompts manually.

```bash
bash .agent/skills/batch-orchestrator/scripts/build-prompt.sh {N} {name} {task_file} [output_dir]
```

This script automatically:
1. Injects full `coding-conventions.md` content
2. Injects full `laravel-common-cores.md` content
3. Injects full `definition-of-done.md` content
4. Adds architecture summary
5. Appends your batch-specific task
6. Adds compliance checklist at the end

**Task File Format** (what you provide):
```
## SCOPE (DO NOT touch files outside this scope)
[List exact files to create/modify]

## APIs to implement (if applicable)
[List each API with endpoint, method, brief description]

## TASK
[Detailed instructions]

## VERIFICATION
After completing, run:
[test/lint commands]
```

### 3.2 Gemini CLI Invocation

**Command format:**

```bash
gemini -p "$(cat .agent/batch-prompts/batch-{N}-{name}.txt)" --approval-mode yolo 2>&1 | tee .agent/logs/batch-{N}-output.log
```

**Or via the run-batch script (recommended):**
```bash
bash .agent/skills/batch-orchestrator/scripts/run-batch.sh {N} {name} .agent/batch-prompts/batch-{N}-{name}.txt yolo
```

**Flags explained:**
- `-p "..."`: Non-interactive/headless mode
- `--approval-mode yolo`: Auto-approve all file edits and operations (required default behavior).

### 3.3 Execution Modes

#### Sequential (Default for dependent batches)
```
Batch 1 → Wait → Verify → Batch 2 → Wait → Verify → ...
```
- Run one at a time
- Verify output before proceeding
- Stop pipeline on failure

#### Parallel (For independent batches)
```
Batch 1 ─┐
Batch 2 ─┼→ Wait All → Verify All
Batch 3 ─┘
```
- Run concurrent Gemini CLI processes (via `run_command` to open terminal UI tabs).
- **LIMIT:** MAXIMUM 3 CONCURRENT PROCESSES to prevent RAM/CPU crash. Chunk if >3 batches.
- Each batch works on different files (NO overlap)
- Merge and verify after all complete

#### Mixed (Most common)
```
[Batch 1: Infrastructure] → Verify
    ↓
[Batch 2: Feature A] ─┐
[Batch 3: Feature B] ─┤→ Verify All
[Batch 4: Feature C] ─┘
    ↓
[Batch 5: Integration Tests] → Verify
```

### 3.4 Monitoring & Logging

1. Create log directory: `mkdir -p .agent/logs`
2. Each batch logs to: `.agent/logs/batch-{N}-{name}.log`
3. After each batch, check:
   - Exit code (0 = success)
   - Test results in output
   - Any error patterns

---

## 4. ✅ Verification Phase

### 4.1 Per-Batch Verification
After each batch completes:
1. Check log file for errors
2. Run batch-specific tests
3. Check for file conflicts (if parallel)

### 4.2 Full Verification
After ALL batches complete:
1. Run full test suite: `php artisan test`
2. Run linter
3. Verify no regression

### 4.3 Failure Handling
If a batch fails:
1. **Analyze log**: Stop pipeline immediately and summarize the failure from `.agent/logs/`.
2. **Conflict Self-Coordination**: If the failure occurred during parallel execution due to a conflict (e.g. file lock, database temp state) caused by another running batch, **DO NOT permanently halt**. Simply wait for the other batch to finish successfully, resync context, and trigger a retry of the failed batch.
3. For hard code errors: Fix the prompt or manually intervene.
4. **DO NOT** proceed to dependent batches until original batch passes.

---

## 5. 📊 Reporting

After execution, create/update `task.md` with:
- Batch execution summary (pass/fail per batch)
- Files created/modified per batch
- Test results
- Any issues encountered and how they were resolved

---

## 6. ⚠️ Safety Rules

1. **NO Git Stash**: Skip pre-batch checkpoints. Run directly on the current branch without stashing.
2. **DEFAULT YOLO**: Always use `--approval-mode yolo` (or parameter `yolo`) to auto-approve code execution.
3. **NEVER** let parallel batches intentionally modify the same file.
4. If a failure happens due to an unintended parallel conflict, pause and self-coordinate (wait -> retry) rather than full failure abortion.
5. **ALWAYS** verify batch output before proceeding to next dependent batch.
