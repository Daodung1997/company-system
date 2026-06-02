---
trigger: always_on
---

# Definition of Done (DoD)

No User Story is considered complete until ALL criteria are met:
1.  **Unit Tests:** Code coverage must be > 80%. All new logic must have corresponding test cases.
2.  **Acceptance Criteria:** The solution must satisfy all acceptance criteria listed in the prompt.
3.  **Non-functional Requirements (NFRs):**
    - Performance: No blocking operations on the main thread.
    - Security: No hardcoded secrets. Inputs must be validated.
4.  **Documentation:**
5.  **Clean Code:** Code must pass the project's Linter without errors.