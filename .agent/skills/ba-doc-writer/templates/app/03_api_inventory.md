# API Inventory (App-level)

## 1. Conventions
- Base path: [/api/v1]
- Auth: [Bearer JWT / Session / ...]
- Error format (draft): `{ code, message, details }`

## 2. Inventory by Module
### Module: <MODULE_NAME>
| UC Ref | Endpoint | Method | Purpose | Auth | Notes |
|---|---|---:|---|---|---|
| UC-01 | /<resource> | GET | List | Yes/No |  |
| UC-01 | /<resource>/{id} | GET | Detail | Yes/No |  |
| UC-02 | /<resource> | POST | Create | Yes/No |  |
| UC-02 | /<resource>/{id} | PUT/PATCH | Update | Yes/No |  |
| UC-xx | /<resource>/{id} | DELETE | Delete | Yes/No | soft delete? |

## 3. Missing / To-be-defined
- [OPEN] Endpoint cho ... chưa rõ
- [OPEN] Permission model chưa rõ

## 4. Assumptions & Open Questions
- [ASSUMPTION] ...
- [OPEN] ...
