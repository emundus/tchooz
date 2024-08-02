***

# EmundusModelMessages





* Full name: `\EmundusModelMessages`
* Parent class: [`ListModel`](./Joomla/CMS/MVC/Model/ListModel.md)



## Properties


### user



```php
private $user
```






***

### db



```php
private $db
```






***

## Methods


### __construct

Constructor

```php
public __construct(array $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** |  |





***

### getAllMessages

Gets all published message templates of a certain type.

```php
public getAllMessages(int $type = 2): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **int** | The type of email to get, type 2 is by default (Templates). |


**Return Value:**

False if the query fails and nothing can be loaded. An array of objects describing the messages. (sender, subject, body, etc..)




***

### getAllCategories

Gets all published message categories of a certain type.

```php
public getAllCategories(int $type = 2): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **int** | The type of category to get, type 2 is by default (Templates). |


**Return Value:**

False if the query fails and nothing can be loaded. An array of the categories.




***

### getAttachments

Gets all published attachments unless a filter is active.

```php
public getAttachments(): bool|array
```









**Return Value:**

False if the query fails and nothing can be loaded. or An array of objects describing attachments.




***

### getLetters

Gets all published letters unless a filter is active.

```php
public getLetters(): bool
```









**Return Value:**

False if the query fails and nothing can be loaded.




***

### getEmail

Gets a message template.

```php
public getEmail(mixed $id): object
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |


**Return Value:**

The email we seek, false if none is found.




***

### getEmailsByCategory

Gets the email templates by using the category.

```php
public getEmailsByCategory(string $category): object|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$category` | **string** | The name of the category. |


**Return Value:**

The list of templates corresponding.




***

### get_upload

Gets the a file from the setup_attachment table linked to an fnum.

```php
public get_upload(string $fnum, int $attachment_id): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **string** | the fnum used for getting the attachment. |
| `$attachment_id` | **int** | the ID of the attachment used in setup_attachment |





***

### get_filename

Gets the a file type label from the setup_attachment table .

```php
public get_filename(int $attachment_id): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attachment_id` | **int** | the ID of the attachment used in setup_attachment |





***

### get_letter

Gets the a file from the setup_letters table linked to an fnum.

```php
public get_letter(int $letter_id): object|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$letter_id` | **int** | the ID of the letter used in setup_letters |


**Return Value:**

The letter object as found in the DB, also contains the status and training.




***

### getCandidateFileNames

Gets the names of candidate files.

```php
public getCandidateFileNames(mixed $ids): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |


**Return Value:**

A list of objects containing the names and ids of the candidate files.




***

### getLetterFileNames

Gets the names of candidate files.

```php
public getLetterFileNames(mixed $ids): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |


**Return Value:**

A list of objects containing the names and ids of the candidate files.




***

### generateLetterDoc

Generates a DOC file for setup_letters

```php
public generateLetterDoc(object $letter, string $fnum): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$letter` | **object** | The template for the doc to create. |
| `$fnum` | **string** | The fnum used to generate the tags. |


**Return Value:**

The path to the saved file.



**Throws:**

- [`CopyFileException`](./PhpOffice/PhpWord/Exception/CopyFileException.md)

- [`CreateTemporaryFileException`](./PhpOffice/PhpWord/Exception/CreateTemporaryFileException.md)

- [`Exception`](./PhpOffice/PhpWord/Exception/Exception.md)



***

### getContacts

get all contacts the current user has received or sent a message as well as their latest message.

```php
public getContacts(null $user = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **null** |  |





***

### updateMessages

gets all messages received after the message $lastID

```php
public updateMessages(mixed $lastId, null $user = null, null $other_user = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lastId` | **mixed** |  |
| `$user` | **null** |  |
| `$other_user` | **null** |  |





***

### getUnread

Get number of unread messages between two users (messages with folder_id 2)

```php
public getUnread(mixed $sender, null $receiver = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sender` | **mixed** |  |
| `$receiver` | **null** |  |





***

### loadMessages

load messages between two users ( messages with folder_id 2 )

```php
public loadMessages(mixed $user1, null $user2 = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user1` | **mixed** |  |
| `$user2` | **null** |  |





***

### sendMessage

sends message folder_id=2 from user_from to user_to and sets stats to 1

```php
public sendMessage(mixed $receiver, mixed $message, null $user = null, bool $system_message = false): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$receiver` | **mixed** |  |
| `$message` | **mixed** |  |
| `$user` | **null** |  |
| `$system_message` | **bool** |  |





***

### deleteSystemMessages



```php
public deleteSystemMessages(mixed $user1, mixed $user2): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user1` | **mixed** |  |
| `$user2` | **mixed** |  |





***

### createChatroom



```php
public createChatroom(null $fnum = null, null $id = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **null** |  |
| `$id` | **null** |  |





***

### joinChatroom



```php
public joinChatroom(int $chatroom, mixed $users): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom` | **int** | Chatroom id, if the room doesn&#039;t exist, it will be created. |
| `$users` | **mixed** | Function is called as such : joinChatroom(4, $user1, $user2, $user3); |





***

### sendChatroomMessage



```php
public sendChatroomMessage(int $chatroom, string $message): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom` | **int** | PAGE column in jos_messages is used to indicate that it&#039;s |
| `$message` | **string** |  |





***

### getChatroomMessages



```php
public getChatroomMessages(int $chatroom): array|bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom` | **int** |  |





***

### updateChatroomMessages

gets all messages received after the message $lastID

```php
public updateChatroomMessages(mixed $lastId, int $chatroom): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lastId` | **mixed** |  |
| `$chatroom` | **int** |  |





***

### getChatroom



```php
public getChatroom(mixed $id): bool|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getChatroomUsersId



```php
public getChatroomUsersId(int $chatroom_id): bool|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom_id` | **int** |  |





***

### getChatroomByUsers



```php
public getChatroomByUsers(mixed $users): bool|int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |





***

### chatRoomExists



```php
private chatRoomExists(mixed $chatroom): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom` | **mixed** |  |





***

### getMessageRecapByFnum



```php
public getMessageRecapByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getTagsByEmail



```php
public getTagsByEmail(mixed $eid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |





***

### addTagsByFnum



```php
public addTagsByFnum(mixed $fnum, mixed $tmpl): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$tmpl` | **mixed** |  |





***

### getActionByFnum



```php
public getActionByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getAllDocumentsLetters



```php
public getAllDocumentsLetters(): mixed
```












***

### getAttachmentsByProfiles



```php
public getAttachmentsByProfiles(mixed $fnums = []): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getAllAttachments



```php
public getAllAttachments(): mixed
```












***

### addTagsByFnums



```php
public addTagsByFnums(mixed $fnums, mixed $tmpl): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$tmpl` | **mixed** |  |





***

### deleteMessagesBeforeADate



```php
public deleteMessagesBeforeADate(mixed $date): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | **mixed** | DateTime  Date to delete messages before |





***

### exportMessagesBeforeADate



```php
public exportMessagesBeforeADate(mixed $date): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | **mixed** | DateTime  Date to export messages before |





***


***
> Automatically generated on 2024-08-02
