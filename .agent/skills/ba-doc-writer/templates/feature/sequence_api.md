@startuml
actor User
boundary "UI/Client" as UI
control "API (Laravel Controller)" as API
control "Service" as SVC
database "DB" as DB
collections "External API" as EXT

User -> UI : Action / Trigger
UI -> API : HTTP Request
API -> SVC : validate + authorize + execute
SVC -> DB : queries (read/write)
DB --> SVC : result

alt External call required
  SVC -> EXT : call external
  EXT --> SVC : response
end

SVC --> API : Model / Collection
API -> API : new Resource(data)
API --> UI : HTTP Response
UI --> User : Render result

@enduml
