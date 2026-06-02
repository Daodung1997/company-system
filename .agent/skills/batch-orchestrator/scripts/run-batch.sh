#!/bin/bash
# =============================================================
# Batch Runner Script for Gemini CLI
# Usage: ./run-batch.sh <batch_number> <batch_name> <prompt_file> [approval_mode]
#
# Arguments:
#   batch_number  - Batch sequential number (1, 2, 3...)
#   batch_name    - Short name for the batch (e.g., "auth-module")
#   prompt_file   - Path to a text file containing the full prompt
#   approval_mode - Optional: "auto_edit" (default) or "yolo"
# =============================================================

set -euo pipefail

BATCH_NUM="${1:?Error: batch_number required}"
BATCH_NAME="${2:?Error: batch_name required}"
PROMPT_FILE="${3:?Error: prompt_file required}"
APPROVAL_MODE="${4:-auto_edit}"

# Resolve project root
PROJECT_ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
LOG_DIR="${PROJECT_ROOT}/.agent/logs"

# Ensure log directory exists
mkdir -p "${LOG_DIR}"

# Timestamp for log file
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="${LOG_DIR}/batch-${BATCH_NUM}-${BATCH_NAME}-${TIMESTAMP}.log"

# Validate prompt file exists
if [ ! -f "${PROMPT_FILE}" ]; then
    echo "❌ Error: Prompt file not found: ${PROMPT_FILE}"
    exit 1
fi

# Read prompt from file
PROMPT=$(cat "${PROMPT_FILE}")

echo "┌─────────────────────────────────────────────┐"
echo "│  🚀 Batch ${BATCH_NUM}: ${BATCH_NAME}"
echo "│  Mode: ${APPROVAL_MODE}"
echo "│  Log:  ${LOG_FILE}"
echo "│  Started: $(date '+%Y-%m-%d %H:%M:%S')"
echo "└─────────────────────────────────────────────┘"

# Run Gemini CLI with the batch prompt
cd "${PROJECT_ROOT}"
PROMPT=$(cat "${PROMPT_FILE}")
gemini -p "${PROMPT}" --approval-mode "${APPROVAL_MODE}" 2>&1 | tee "${LOG_FILE}"

EXIT_CODE=${PIPESTATUS[0]}

echo ""
echo "┌─────────────────────────────────────────────┐"
if [ ${EXIT_CODE} -eq 0 ]; then
    echo "│  ✅ Batch ${BATCH_NUM} completed successfully"
    
    # 1. Clean up prompt files to prevent blocking future batches
    rm -f "${PROMPT_FILE}"
    # If the prompt file is like batch-1-name.txt, also try to remove task-1-name.txt
    TASK_FILE=$(echo "${PROMPT_FILE}" | sed 's/batch-/task-/g')
    if [ -f "${TASK_FILE}" ]; then
        rm -f "${TASK_FILE}"
    fi

    # 2. Append to a global batch report
    REPORT_FILE="${LOG_DIR}/../batch-report.md"
    echo "- ✅ **Batch ${BATCH_NUM} (${BATCH_NAME})**: Thành công lúc $(date '+%Y-%m-%d %H:%M:%S'). Log file: \`${LOG_FILE}\`" >> "${REPORT_FILE}"
else
    echo "│  ❌ Batch ${BATCH_NUM} FAILED (exit code: ${EXIT_CODE})"
    # Record failure
    REPORT_FILE="${LOG_DIR}/../batch-report.md"
    echo "- ❌ **Batch ${BATCH_NUM} (${BATCH_NAME})**: THẤT BẠI lúc $(date '+%Y-%m-%d %H:%M:%S'). Log file: \`${LOG_FILE}\`" >> "${REPORT_FILE}"
fi
echo "│  Finished: $(date '+%Y-%m-%d %H:%M:%S')"
echo "│  Log: ${LOG_FILE}"
echo "└─────────────────────────────────────────────┘"

exit ${EXIT_CODE}
