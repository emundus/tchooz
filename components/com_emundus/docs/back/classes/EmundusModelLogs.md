***

# EmundusModelLogs





* Full name: `\EmundusModelLogs`
* Parent class: [`JModelList`](./JModelList.md)



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

EmundusModelLogs constructor.

```php
public __construct(): mixed
```













***

### log

Writes a log entry of the action to/from the user.

```php
public static log(int $user_from, int $user_to, string $fnum, int $action, string $crud = &#039;&#039;, string $message = &#039;&#039;, mixed $params = &#039;&#039;): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_from` | **int** |  |
| `$user_to` | **int** |  |
| `$fnum` | **string** |  |
| `$action` | **int** |  |
| `$crud` | **string** |  |
| `$message` | **string** |  |
| `$params` | **mixed** |  |






***

### logs



```php
public static logs(mixed $user_from, mixed $fnums, mixed $action, mixed $crud = &#039;&#039;, mixed $message = &#039;&#039;, mixed $params = &#039;&#039;): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_from` | **mixed** |  |
| `$fnums` | **mixed** |  |
| `$action` | **mixed** |  |
| `$crud` | **mixed** |  |
| `$message` | **mixed** |  |
| `$params` | **mixed** |  |






***

### getUserActions

Gets the actions done by a user. Can be filtered by action and/or CRUD.

```php
public getUserActions(int $user_from = null, int $action = null, string $crud = null): mixed
```

If the user is not specified, use the currently signed in one.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_from` | **int** |  |
| `$action` | **int** |  |
| `$crud` | **string** |  |


**Return Value:**

Returns false on error and an array of objects on success.





***

### getActionsOnUser

Gets the actions done on a user. Can be filtered by action and/or CRUD.

```php
public getActionsOnUser(int $user_to = null, int $action = null, string $crud = null): mixed
```

If no user_id is sent: use the currently signed in user.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_to` | **int** |  |
| `$action` | **int** |  |
| `$crud` | **string** |  |


**Return Value:**

Returns false on error and an array of objects on success.





***

### getActionsOnFnum

Gets the actions done on an fnum. Can be filtered by user doing the action, the action itself, CRUD and/or banned logs.

```php
public getActionsOnFnum(int $fnum, array $user_from = null, array $action = null, array $crud = null, int $offset = null, int $limit = 100): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **int** |  |
| `$user_from` | **array** | // optional |
| `$action` | **array** | // optional |
| `$crud` | **array** | // optional |
| `$offset` | **int** |  |
| `$limit` | **int** |  |


**Return Value:**

Returns false on error and an array of objects on success.





***

### getActionsBetweenUsers

Gets the actions done by users on each other. In both directions.

```php
public getActionsBetweenUsers(int $user1, int $user2 = null, int $action = null, string $crud = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user1` | **int** |  |
| `$user2` | **int** |  |
| `$action` | **int** |  |
| `$crud` | **string** |  |


**Return Value:**

Returns false on error and an array of objects on success.





***

### setActionDetails

Writes the details that will be shown in the logs menu.

```php
public setActionDetails(int $action = null, string $crud = null, string $params = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$action` | **int** |  |
| `$crud` | **string** |  |
| `$params` | **string** |  |


**Return Value:**

Returns false on error and an array of strings on success.





***

### exportLogs



```php
public exportLogs(mixed $fnum, mixed $users, mixed $actions, mixed $crud): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$users` | **mixed** |  |
| `$actions` | **mixed** |  |
| `$crud` | **mixed** |  |






***

### getUsersLogsByFnum



```php
public getUsersLogsByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### deleteLogsBeforeADate



```php
public deleteLogsBeforeADate(mixed $date): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | **mixed** | DateTime  Date to delete logs before |






***

### exportLogsBeforeADate



```php
public exportLogsBeforeADate(mixed $date): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | **mixed** | DateTime  Date to export logs before |






***


***
> Last updated on 20/08/2024
