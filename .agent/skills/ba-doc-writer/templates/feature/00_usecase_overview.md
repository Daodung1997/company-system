# Use Case Overview (Module/Feature)

## 1. Scope
- Feature/Module: [<name>]
- Boundary: [what is included/excluded]

## 2. Actors
| Actor | Description |
|---|---|
| User |  |
| Admin |  |
| System |  |

## 3. Use Case List
| UC ID | Name | Actor | 1-line Goal | Notes |
|---|---|---|---|---|
| UC-01 |  |  |  |  |
| UC-02 |  |  |  |  |

## 3.1 Use Case overview

@startuml
left to right direction

actor "User" as U
actor "Admin" as A
actor "System" as S

rectangle "System: <FEATURE/MODULE_NAME>" {
  usecase "UC-01: <name>" as UC01
  usecase "UC-02: <name>" as UC02
  usecase "UC-03: <name>" as UC03

  ' include/extend examples:
  ' UC02 ..> UC01 : <<include>>
  ' UC03 ..> UC02 : <<extend>>
}

U --> UC01
U --> UC02
A --> UC03
S --> UC02

@enduml
## 4. Assumptions & Open Questions
- [ASSUMPTION] ...
- [OPEN] ...
