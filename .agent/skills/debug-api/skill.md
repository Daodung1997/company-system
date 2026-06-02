---
name: debug-api
description: Systematic process to debug API failures by tracing logs, exposing raw errors, and isolating DB issues.
---

# Debug API Skill

> **Goal**: Identify the root cause of an API failure by peeling back layers of error handling until the raw error is exposed.

---

## 1. 🔍 Reproduce & Observe
**Action**: Run the failing test case or API request.

1. **Run Test**: `php artisan test --filter MethodName`
   - Capture the output.
2. **Check Status**:
   - If `500`: The error is likely in logs.
   - If `200` but wrong data: Logic error.
   - If `4xx`: Validation or Auth error.

3. **Check Logs**:
   - Run `tail -n 50 storage/logs/laravel.log` to see the latest stack trace.

---

## 2. 🕵️ Trace & Expose (Iterative)
**Action**: If the error is generic (e.g., "Server Error" or custom 500 message) and logs are empty/vague because of `try-catch`:

1. **Identify Catch Block**: Locate the Controller or Service method handling the request.
2. **Remove Try-Catch**:
   - **TEMPORARILY** comment out `try {`, `} catch (...) { ... }` blocks to let the raw exception bubble up to the global handler (which usually logs the full trace).
   - Use `replace_file_content` to comment them out.
3. **Re-run**: Execute step 1 again.
4. **Analyze New Trace**: Look for the exact file and line number of the origin.

---

## 3. 🛑 Database Error Protocol (MANDATORY STOP)
**Condition**: If the root cause is a **Database Error** (PDOException, SQLState, Missing Column, Integrity Constraint):

1. **STOP IMMEDIATELY**. Do not try to auto-fix DB schema issues.
2. **Analyze**:
   - What table/column is missing?
   - What query failed?
   - Is it a migration sync issue?
3. **Report**:
   - Describe the error clearly.
   - Propose solutions (e.g., "Run migration", "Edit migration", "Fix Query", "Update Model").
4. **Ask User**: "Lỗi DB: [Mô tả]. Bạn muốn xử lý thế nào? (Options 1, 2...)"

---

## 4. 🛠 logic Error Protocol
**Condition**: If code logic (PHP) error (NullPointer, wrong calculation):

1. **Fix**: Apply fix if obvious.
2. **Verify**: Re-run test.
3. **Clean up**: **Uncomment** the try-catch blocks (restore original error handling) once fixed.

---

## 5. ✅ Definition of Done
1. Test passes.
2. `try-catch` blocks restored (if they were correct behavior).
3. No debug dumps left in code.
