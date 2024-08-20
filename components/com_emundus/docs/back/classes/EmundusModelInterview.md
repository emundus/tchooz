***

# EmundusModelInterview





* Full name: `\EmundusModelInterview`
* Parent class: [`JModelList`](./JModelList.md)




## Methods


### __construct



```php
public __construct(): mixed
```












***

### getInterviewFormByProgramme



```php
public getInterviewFormByProgramme(mixed $code = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getGroupsInterviewByProgramme



```php
public getGroupsInterviewByProgramme(mixed $code): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getEvaluationsFnumUser



```php
public getEvaluationsFnumUser(mixed $fnum, mixed $user): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$user` | **mixed** |  |





***

### getEvaluationsByFnum



```php
public getEvaluationsByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getAllElementsByGroups



```php
public getAllElementsByGroups(mixed $groups): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groups` | **mixed** |  |





***

### getAllInterviewElements

Get list of ALL evaluation element

```php
public getAllInterviewElements(int $show_in_list_summary, mixed $programme_code): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **int** |  |
| `$programme_code` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in evaluation form



**Throws:**

- [`Exception`](./Exception.md)



***


***
> Automatically generated on 2024-08-20
