#!/bin/bash
# =============================================================
# Parallel Batch Runner - Runs multiple independent batches
# Usage: ./run-parallel.sh <prompt_dir> [approval_mode]
#
# Arguments:
#   prompt_dir    - Directory containing prompt files named: batch-{N}-{name}.txt
#   approval_mode - Optional: "auto_edit" (default) or "yolo"
#
# Example file naming:
#   prompts/batch-2-feature-a.txt
#   prompts/batch-3-feature-b.txt
#   prompts/batch-4-feature-c.txt
# =============================================================

set -euo pipefail

PROMPT_DIR="${1:?Error: prompt_dir required}"
APPROVAL_MODE="${2:-auto_edit}"

# Resolve project root
PROJECT_ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
LOG_DIR="${PROJECT_ROOT}/.agent/logs"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
RUN_BATCH="${SCRIPT_DIR}/run-batch.sh"

# Validate
if [ ! -d "${PROMPT_DIR}" ]; then
    echo "❌ Error: Prompt directory not found: ${PROMPT_DIR}"
    exit 1
fi

# Collect prompt files
PROMPT_FILES=($(ls "${PROMPT_DIR}"/batch-*.txt 2>/dev/null | sort))

if [ ${#PROMPT_FILES[@]} -eq 0 ]; then
    echo "❌ No batch prompt files found in ${PROMPT_DIR}"
    echo "   Expected format: batch-{N}-{name}.txt"
    exit 1
fi

echo "╔═════════════════════════════════════════════╗"
echo "║  🔄 Parallel Batch Execution"
echo "║  Batches: ${#PROMPT_FILES[@]}"
echo "║  Mode: ${APPROVAL_MODE}"
echo "║  Started: $(date '+%Y-%m-%d %H:%M:%S')"
echo "╚═════════════════════════════════════════════╝"

# Array to track PIDs and their batch info
declare -a PIDS=()
declare -a BATCH_NAMES=()

for PROMPT_FILE in "${PROMPT_FILES[@]}"; do
    # Extract batch number and name from filename
    FILENAME=$(basename "${PROMPT_FILE}" .txt)
    BATCH_NUM=$(echo "${FILENAME}" | sed 's/batch-\([0-9]*\)-.*/\1/')
    BATCH_NAME=$(echo "${FILENAME}" | sed 's/batch-[0-9]*-//')

    echo "  → Starting Batch ${BATCH_NUM}: ${BATCH_NAME}..."
    
    # Run batch in background
    bash "${RUN_BATCH}" "${BATCH_NUM}" "${BATCH_NAME}" "${PROMPT_FILE}" "${APPROVAL_MODE}" &
    PIDS+=($!)
    BATCH_NAMES+=("Batch ${BATCH_NUM}: ${BATCH_NAME}")
done

echo ""
echo "⏳ Waiting for all batches to complete..."
echo ""

# Wait for all and collect results
FAILED=0
for i in "${!PIDS[@]}"; do
    PID=${PIDS[$i]}
    NAME=${BATCH_NAMES[$i]}
    
    if wait "${PID}"; then
        echo "  ✅ ${NAME} - SUCCESS"
    else
        echo "  ❌ ${NAME} - FAILED"
        FAILED=$((FAILED + 1))
    fi
done

echo ""
echo "╔═════════════════════════════════════════════╗"
if [ ${FAILED} -eq 0 ]; then
    echo "║  ✅ All ${#PIDS[@]} batches completed successfully"
else
    echo "║  ⚠️  ${FAILED}/${#PIDS[@]} batches FAILED"
fi
echo "║  Finished: $(date '+%Y-%m-%d %H:%M:%S')"
echo "╚═════════════════════════════════════════════╝"

exit ${FAILED}
