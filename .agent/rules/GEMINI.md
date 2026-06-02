---
trigger: always_on
---

# GEMINI.md - Maestro Configuration

> **Maestro AI Orchestrator**
> This file defines the MANDATORY behavior for this workspace.

---

## 🚨 1. CRITICAL PROTOCOL (PRIORITY P0)

**MANDATORY:** You MUST read and apply rules in this order:
1. **`GEMINI.md`** (This file) - Global Rules.
2. **`@[.agent/rules/coding-conventions.md]`** - Project-Specific Code Rules.
3. **Agent File** (if active) - Role-specific behavior.
4. **Skill Files** (`SKILL.md`) - Task-specific instructions.

> **NEVER SKIP** reading the above files before coding. "Read → Understand → Apply" is enforced.

---

## 🛑 2. CORE RULES (ALWAYS ACTIVE)

### 🌐 Language & Communication
- **User Language**: Vietnamese (Tiếng Việt).
- **Code/Comments**: English (Tiếng Anh).
- **Style**: Direct, professional, no fluff.

### 📜 Coding Standards
**ALL code MUST follow `@[.agent/rules/coding-conventions.md]`.**
- **Response**: ALWAYS use `Response::success/created/failure`.
- **Architecture**: Controller → Service (AbstractService) → Repository (Criteria).
- **Security**: IDOR checks, Data Leakage prevention (Resource), Input Validation (RequestTrait).

### 📚 System Reference
- **Reusable Components**: Check `@[.agent/rules/laravel-common-cores.md]` first.
- **Don't reinvent**: Use existing Constants, Traits, BaseModels.

### ✅ Definition of Done
**Tasks are NOT complete until:**
1. Code follows **`coding-conventions.md`**.
2. **Unit Tests** > 80% coverage for new logic.
3. **Linter** passes.
4. Meets criteria in `@[.agent/rules/definition-of-done.md]`.

### Diagram
 - always use PlantUML
---

## 🧠 3. WORKING PROCESS

### 🛑 Socratic Gate (Assessment)
Before implementation, **STOP & ASK** if:
1. Requirements are vague (< 90% clear).
2. High-risk changes (Auth, Money, Data structure).
3. "Edge cases" are undefined.

### 🎭 Execution Modes
| Mode | Action |
|------|--------|
| **PLANNING** | Analyze → Research → Create `{task-slug}.md`. NO CODE. |
| **EXECUTION** | Implement per Plan → Writes Code → Tests. |
| **VERIFICATION** | Verify vs Requirements → Run Tests → Review Artifacts. |

---

## 🛠 4. TOOLING & SKILLS

### 📁 File Safety
- **Before Edit**: Read `CODEBASE.md` (if exists) & `ARCHITECTURE.md`.
- **Dependencies**: Identify and update ALL dependent files.

### 🧩 Key Skills
- **`clean-code`**: Global clean code standards.
- **`brainstorming`**: For complex requirement analysis.
- **`plan-writing`**: For structured task execution.
- **`api-patterns`**: RESTful API best practices.

---