***

# EmundusModelJob

Emundus model.



* Full name: `\EmundusModelJob`
* Parent class: [`JModelItem`](./JModelItem.md)




## Methods


### populateState

Method to auto-populate the model state.

```php
protected populateState(): mixed
```

Note. Calling getState in this method will result in recursion.










***

### getData

Method to get an ojbect.

```php
public getData(mixed $id = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |


**Return Value:**

Object on success, false on failure.




***

### getTable



```php
public getTable(mixed $type = &#039;Job&#039;, mixed $prefix = &#039;EmundusTable&#039;, mixed $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **mixed** |  |
| `$prefix` | **mixed** |  |
| `$config` | **mixed** |  |





***

### checkin

Method to check in an item.

```php
public checkin(mixed $id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |


**Return Value:**

True on success, false on failure.




***

### checkout

Method to check out an item for editing.

```php
public checkout(mixed $id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |


**Return Value:**

True on success, false on failure.




***

### getCategoryName



```php
public getCategoryName(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### publish



```php
public publish(mixed $id, mixed $state): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$state` | **mixed** |  |





***

### delete



```php
public delete(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### cancel

Method to cancel application to a job

```php
public cancel(int $user_id, string $fnum): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **int** | The id of the user. |
| `$fnum` | **string** | The fnum of an application. |


**Return Value:**

True on success, false on failure.




***

### apply

Method to apply to a job

```php
public apply(mixed $user_id, mixed $job_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$job_id` | **mixed** |  |


**Return Value:**

True on success, false on failure.




***


***
> Automatically generated on 2024-08-20
