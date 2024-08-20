***

# EmundusModelMessenger





* Full name: `\EmundusModelMessenger`
* Parent class: [`JModelList`](./JModelList.md)




## Methods


### __construct



```php
public __construct(mixed $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **mixed** |  |





***

### getFilesByUser



```php
public getFilesByUser(): mixed
```












***

### getMessagesByFnum



```php
public getMessagesByFnum(mixed $fnum, mixed $offset): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$offset` | **mixed** |  |





***

### sendMessage



```php
public sendMessage(mixed $message, mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$message` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### getMessageById



```php
public getMessageById(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getNotifications



```php
public getNotifications(mixed $user): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |





***

### getNotificationsByFnum



```php
public getNotificationsByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### markAsRead



```php
public markAsRead(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getDocumentsByCampaign



```php
public getDocumentsByCampaign(mixed $fnum, mixed $applicant): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$applicant` | **mixed** |  |





***

### askAttachment



```php
public askAttachment(mixed $fnum, mixed $attachment, mixed $message): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$attachment` | **mixed** |  |
| `$message` | **mixed** |  |





***

### moveToUploadedFile



```php
public moveToUploadedFile(mixed $fnumInfos, mixed $attachment, mixed $filesrc, mixed $target_file): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnumInfos` | **mixed** |  |
| `$attachment` | **mixed** |  |
| `$filesrc` | **mixed** |  |
| `$target_file` | **mixed** |  |





***

### notifyByMail



```php
public notifyByMail(mixed $applicant_fnum, mixed $notify_applicant): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$applicant_fnum` | **mixed** |  |
| `$notify_applicant` | **mixed** |  |





***


***
> Automatically generated on 2024-08-20
