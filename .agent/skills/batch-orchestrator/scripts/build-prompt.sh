#!/bin/bash
# =============================================================
# Build Batch Prompt with Full Project Rules Injected
# Usage: ./build-prompt.sh <batch_number> <batch_name> <task_file> [output_dir]
#
# This script combines:
#   1. Project rules (coding-conventions, laravel-common-cores)
#   2. Architecture context from GEMINI.md
#   3. The specific batch task description
# Into a single self-contained prompt file that Gemini CLI can execute.
#
# Arguments:
#   batch_number - Sequential batch number (1, 2, 3...)
#   batch_name   - Short name (e.g., "infrastructure", "payment-apis")
#   task_file    - Path to a .txt file with the batch-specific TASK section
#   output_dir   - Optional: output directory (default: .agent/batch-prompts)
# =============================================================

set -euo pipefail

BATCH_NUM="${1:?Error: batch_number required}"
BATCH_NAME="${2:?Error: batch_name required}"
TASK_FILE="${3:?Error: task_file required}"
OUTPUT_DIR="${4:-.agent/batch-prompts}"

# Resolve project root
PROJECT_ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
RULES_DIR="${PROJECT_ROOT}/.agent/rules"

# Validate inputs
if [ ! -f "${TASK_FILE}" ]; then
    echo "❌ Error: Task file not found: ${TASK_FILE}"
    exit 1
fi

# Ensure output directory exists
mkdir -p "${OUTPUT_DIR}"

OUTPUT_FILE="${OUTPUT_DIR}/batch-${BATCH_NUM}-${BATCH_NAME}.txt"

echo "📝 Building prompt for Batch ${BATCH_NUM}: ${BATCH_NAME}..."

# Build the prompt
cat > "${OUTPUT_FILE}" << 'PROMPT_HEADER'
You are implementing code for the viec-vat-ai-be project (Laravel REST API).
You MUST follow ALL project rules strictly. No exceptions.

# ============================================================
# MANDATORY PROJECT RULES (Read and follow ALL of these)
# ============================================================

PROMPT_HEADER

# Inject coding conventions
echo "" >> "${OUTPUT_FILE}"
echo "## === CODING CONVENTIONS (from .agent/rules/coding-conventions.md) ===" >> "${OUTPUT_FILE}"
echo "" >> "${OUTPUT_FILE}"
if [ -f "${RULES_DIR}/coding-conventions.md" ]; then
    cat "${RULES_DIR}/coding-conventions.md" >> "${OUTPUT_FILE}"
else
    echo "WARNING: coding-conventions.md not found" >> "${OUTPUT_FILE}"
fi

# Inject laravel common cores
echo "" >> "${OUTPUT_FILE}"
echo "## === LARAVEL COMMON CORES (from .agent/rules/laravel-common-cores.md) ===" >> "${OUTPUT_FILE}"
echo "" >> "${OUTPUT_FILE}"
if [ -f "${RULES_DIR}/laravel-common-cores.md" ]; then
    cat "${RULES_DIR}/laravel-common-cores.md" >> "${OUTPUT_FILE}"
else
    echo "WARNING: laravel-common-cores.md not found" >> "${OUTPUT_FILE}"
fi

# Inject definition of done
echo "" >> "${OUTPUT_FILE}"
echo "## === DEFINITION OF DONE (from .agent/rules/definition-of-done.md) ===" >> "${OUTPUT_FILE}"
echo "" >> "${OUTPUT_FILE}"
if [ -f "${RULES_DIR}/definition-of-done.md" ]; then
    cat "${RULES_DIR}/definition-of-done.md" >> "${OUTPUT_FILE}"
else
    echo "WARNING: definition-of-done.md not found" >> "${OUTPUT_FILE}"
fi

# Add architecture summary
cat >> "${OUTPUT_FILE}" << 'ARCH_SECTION'

# ============================================================
# ARCHITECTURE (Non-negotiable)
# ============================================================

```
Controller (thin) → Service (extends AbstractService) → Repository (Criteria pattern)
```

- **Controller**: ONLY calls Service methods. Returns `Response::success/created/failure`.
- **Service**: Business logic. MUST use `beginTransaction()/commitTransaction()/rollbackTransaction()`.
- **Repository**: Data access via Criteria. NEVER use `Model::where()` in Service.
- **Request**: FormRequest with `RequestTrait`. Validation messages via `renderMessageFromRule()`.
- **Resource**: Always return Resource, NEVER raw Model (Data Leakage prevention).
- **Constants**: NEVER hardcode statuses/types/roles.
- **Exception**: ONLY throw `BusinessException` with `ExceptionCode`. NEVER `throw new Exception()`.
- **Security**: IDOR checks on all ownership-sensitive operations.

ARCH_SECTION

# Add the batch-specific task
echo "" >> "${OUTPUT_FILE}"
echo "# ============================================================" >> "${OUTPUT_FILE}"
echo "# BATCH TASK: Batch ${BATCH_NUM} - ${BATCH_NAME}" >> "${OUTPUT_FILE}"
echo "# ============================================================" >> "${OUTPUT_FILE}"
echo "" >> "${OUTPUT_FILE}"
cat "${TASK_FILE}" >> "${OUTPUT_FILE}"

# Add compliance reminder at the end
cat >> "${OUTPUT_FILE}" << 'COMPLIANCE_FOOTER'

# ============================================================
# COMPLIANCE CHECKLIST (Verify BEFORE finishing)
# ============================================================

Before you finish, verify ALL of these:
- [ ] Used `Response::success/created/failure` (NOT `response()->json()`)
- [ ] Models extend `BaseModel` or `BaseMasterModel`
- [ ] Used Constants for statuses/types (NO magic strings)
- [ ] Used `RequestTrait` in FormRequest
- [ ] Used Repository pattern (NOT direct Eloquent in Service)
- [ ] Service extends `AbstractService` with proper transactions
- [ ] Resources used (NO raw Model in response)
- [ ] IDOR checks on ownership-sensitive endpoints
- [ ] Files placed in correct module directories
- [ ] Naming follows convention ({Entity}Controller, {Action}{Entity}Request, etc.)
- [ ] Feature tests cover: Success (200/201), Validation (422), Auth (401/403)
- [ ] `assertDatabaseHas` checks ALL fields sent in request body
COMPLIANCE_FOOTER

# Report
LINES=$(wc -l < "${OUTPUT_FILE}")
SIZE=$(wc -c < "${OUTPUT_FILE}" | tr -d ' ')

echo "✅ Prompt built: ${OUTPUT_FILE}"
echo "   Lines: ${LINES}, Size: ${SIZE} bytes"
echo "   Rules injected: coding-conventions, laravel-common-cores, definition-of-done"
