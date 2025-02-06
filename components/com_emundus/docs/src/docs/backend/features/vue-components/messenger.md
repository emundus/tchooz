```mermaid
flowchart TD
    A[Messenger] --> B(Messenger.vue)
    B --> C(MessengerModal.vue)
    C --> D(ChatroomsList.vue)
    D --> |currentChat| E
    C --> E(Chatroom.vue)
```