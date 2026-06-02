# Delivery Plan (Module Roadmap)

## 1. Recommended Order
| Order | Module | Why now? | Dependencies | Risks | Exit Criteria |
|---:|---|---|---|---|---|
| 1 | Auth/RBAC | nền tảng quyền | - |  | login + permission baseline |
| 2 | Core Domain | data chính | Auth |  | CRUD + search |
| 3 | Workflow/Approval | quy trình | Core |  | trạng thái + audit |
| 4 | Reporting/Export | tổng hợp | Core |  | report baseline |
| 5 | Integrations | kết nối ngoài | Auth/Core |  | stable contracts |

## 2. Cross-module Contracts Needed
- Shared entities: User, Role, Tenant, ...
- Event/Queue topics (nếu có): ...
- Naming conventions: ...

## 3. Assumptions & Open Questions
- [ASSUMPTION] ...
- [OPEN] ...
