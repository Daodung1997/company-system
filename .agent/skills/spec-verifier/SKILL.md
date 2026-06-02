---
name: spec-verifier
description: Verify Functional Specifications (docs/features) against Origin Specs (docs/origins) for API completeness, validation accuracy, and FE action sufficiency.
---

# Spec Verifier Skill

**Purpose:** 
Systematically cross-check and verify Functional Specifications (`docs/features/.../functional_spec.md`) against their corresponding Origin Specifications (`docs/origins/.../*.md`) before any code implementation begins.

**Goal:**
Identify any discrepancies, missing requirements, or validation mismatches between what the Business Analyst (BA)/Customer requested (Origin) and what is planned for implementation (Functional Spec). This ensures the Frontend (FE) has all necessary APIs and data to build the UI correctly.

---

## Prerequisites (Information Gathering)
Before running this skill, the Agent must identify the exact pair of documentation to compare:
1. **Origin Document:** Locate the markdown file in `docs/origins/` that defines the user story and requirements for the feature.
2. **Feature Document:** Locate the corresponding `functional_spec.md` in `docs/features/{module}/01_apis/{endpoint}/`.
3. **Read Both:** Thoroughly read both documents entirely before analysis.

---

## Verification Criteria (The Checklist)

The Agent MUST evaluate the Functional Specification against the Origin Specification using the following three critical criteria:

### 1. API Completeness (Are all required fields present?)
- **Input Fields:** Does the Request Payload (`docs/features`) contain all the data fields requested in the "Các trường cần có" section of `docs/origins`?
- **Output Fields:** Does the API Response payload (Data Mapping & Response sections) return all the data the Frontend needs to render the screens described in `docs/origins`?
- **Missing Data:** Are there any hidden fields (like `customer_id`, `status`) implied by the origin flow that are missing in the data mapping?
- **Actor/Role Restrictions (Security):** Are there fields in the origin that are intentionally omitted from the API response because they belong to a different role (e.g., Admin-only fields like `identity_documents` should NOT be returned to the Worker/Customer apps)? Evaluate the security context before marking a missing field as an error.

### 2. Validation Accuracy (Are the rules strict and aligned?)
- **API Type Consideration:** Differentiate between `GET` (fetch) and `POST/PUT` (update) APIs. Request validation checks generally do not apply to `GET` endpoints without payload parameters.
- Compare the "Validate các trường" section in `docs/origins` against the "Validation Rules" in `docs/features` (for mutating APIs).
- Check specifically for:
  - **Lengths:** `max`, `min` character limits (e.g., origin: 255 vs feature: 100).
  - **Types & Formats:** email format, alphanumeric boundaries.
  - **Required vs Optional:** `required` vs `nullable`.
  - **File Constraints:** image formats (JPG/PNG), file sizes (≤ 5MB), maximum number of files.
  - **Business Rules:** custom constraints like unique scopes, date rules (e.g., "không trong quá khứ").

### 3. FE Action Sufficiency (Are there enough endpoints for the UI?)
- **Main Flow:** Can the Frontend complete the primary User Flow described in `docs/origins` using only the defined APIs?
- **Exception/Alternative Flows:** If the origin describes error handling or alternative paths (e.g., resend OTP, reject quotation), are there corresponding APIs defined to handle those actions or responses that trigger them?
- **Response Usability:** Are error codes clearly structured for the FE to display contextual error messages?

---

## Output Format: Verification Report

The Agent MUST generate a concise Markdown report summarizing the findings for the user to review. Do not verify against code in this step.

### Template:
```markdown
# Spec Verification Report: [Feature Name]

**Origin Spec:** \`docs/origins/....md\`
**Feature Spec(s):** \`docs/features/.../functional_spec.md\`

## 1. API Completeness
- ✅ **Match:** [List matching fields]
- ❌ **Missing Input:** [List input fields present in origin but missing in feature spec]
- ❌ **Missing Response Data:** [List data FE needs from origin that API response doesn't provide]
- 🛡️ **Skipped for Security/Role:** [List fields intentionally omitted due to role restrictions, e.g., Admin only]

## 2. Validation Accuracy
| Field | Origin Spec | Feature Spec | Status |
|-------|-------------|--------------|--------|
| name | max:255 | max:100 | ⚠️ Mismatch |
| file | JPG/PNG | (Missing) | ❌ Missing Rule |
| status| required | required | ✅ Match |

## 3. FE Action Sufficiency
- 🟢 **Covered Actions:** [List actions the API handles perfectly]
- 🔴 **Missing Actions/Flows:** [List UX flows from origin that lack API support]

## 4. Recommendation / Decision Point
[Summary of critical gaps that need the User's decision before proceeding to Code Verification or Implementation]
```
