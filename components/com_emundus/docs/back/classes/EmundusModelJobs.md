***

# EmundusModelJobs

Methods supporting a list of Emundus records.



* Full name: `\EmundusModelJobs`
* Parent class: [`JModelList`](./JModelList.md)




## Methods


### __construct

Constructor.

```php
public __construct(mixed $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **mixed** |  |





**See Also:**

* \JController - 

***

### populateState

Method to auto-populate the model state.

```php
protected populateState(mixed $ordering = null, mixed $direction = null): mixed
```

Note. Calling getState in this method will result in recursion.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ordering` | **mixed** |  |
| `$direction` | **mixed** |  |





***

### getListQuery

Build an SQL query to load the list data.

```php
protected getListQuery(): \JDatabaseQuery
```












***

### getItems



```php
public getItems(): mixed
```












***

### loadFormData

Overrides the default function to check Date fields format, identified by
"_dateformat" suffix, and erases the field if it's not correct.

```php
protected loadFormData(): mixed
```












***

### isValidDate

Checks if a given date is valid and in an specified format (YYYY-MM-DD)

```php
private isValidDate(mixed $date): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | **mixed** |  |





***

### getFilterForm

Get the filter form

```php
public getFilterForm(mixed $data = array(), mixed $loadData = true): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |
| `$loadData` | **mixed** |  |





***

### getActiveFilters

Function to get the active filters

```php
public getActiveFilters(): mixed
```












***

### getParameterFromRequest



```php
private getParameterFromRequest(mixed $paramName, mixed $default = null, mixed $type = &#039;string&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$paramName` | **mixed** |  |
| `$default` | **mixed** |  |
| `$type` | **mixed** |  |





***


***
> Automatically generated on 2024-08-02
