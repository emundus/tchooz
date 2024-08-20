***

# EmundusModelJobForm

Emundus model.



* Full name: `\EmundusModelJobForm`
* Parent class: [`JModelForm`](./JModelForm.md)



## Properties


### _item



```php
public $_item
```






***

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

### getForm

Method to get the profile form.

```php
public getForm(array $data = array(), bool $loadData = true): \JForm|false
```

The base form is loaded from XML






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | An optional array of data for the form to interogate. |
| `$loadData` | **bool** | True if the form is to load its own data (default case), false if not. |


**Return Value:**

A JForm object on success, false on failure




***

### loadFormData

Method to get the data that should be injected in the form.

```php
protected loadFormData(): mixed
```









**Return Value:**

The data for the form.




***

### save

Method to save the form data.

```php
public save(mixed $data): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |


**Return Value:**

The user id on success, false on failure.




***

### delete



```php
public delete(mixed $data): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |





***


***
> Automatically generated on 2024-08-20
