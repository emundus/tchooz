***

# EmundusModelGroups





* Full name: `\EmundusModelGroups`
* Parent class: [`JModelList`](./JModelList.md)



## Properties


### app



```php
private $app
```






***

### db



```php
private $db
```






***

### _total



```php
private $_total
```






***

### _pagination



```php
private $_pagination
```






***

## Methods


### __construct

Constructor

```php
public __construct(): mixed
```












***

### _buildContentOrderBy



```php
public _buildContentOrderBy(): mixed
```












***

### getCampaign



```php
public getCampaign(): mixed
```












***

### getProfileAcces



```php
public getProfileAcces(mixed $user): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |





***

### _buildQuery



```php
public _buildQuery(): mixed
```












***

### getUsers



```php
public getUsers(): mixed
```












***

### getProfiles



```php
public getProfiles(): mixed
```












***

### getProfilesByIDs



```php
public getProfilesByIDs(mixed $ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |





***

### getAuthorProfiles



```php
public getAuthorProfiles(): mixed
```












***

### getEvaluators



```php
public getEvaluators(): mixed
```












***

### getApplicantsProfiles



```php
public getApplicantsProfiles(): mixed
```












***

### getApplicantsByProfile



```php
public getApplicantsByProfile(mixed $profile): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile` | **mixed** |  |





***

### getGroups



```php
public getGroups(): mixed
```












***

### getGroupsByCourse



```php
public getGroupsByCourse(mixed $course): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$course` | **mixed** |  |





***

### getGroupsIdByCourse



```php
public getGroupsIdByCourse(mixed $course): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$course` | **mixed** |  |





***

### getGroupsEval



```php
public getGroupsEval(): mixed
```












***

### getUsersGroups



```php
public getUsersGroups(): mixed
```












***

### getUsersByGroup



```php
public getUsersByGroup(mixed $gid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### getUsersByGroups



```php
public getUsersByGroups(mixed $gids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gids` | **mixed** |  |





***

### affectEvaluatorsGroups



```php
public affectEvaluatorsGroups(mixed $groups, mixed $aid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groups` | **mixed** |  |
| `$aid` | **mixed** |  |





***

### getAuthorUsers



```php
public getAuthorUsers(): mixed
```












***

### getMobility



```php
public getMobility(): mixed
```












***

### getElements



```php
public getElements(): mixed
```












***

### getTotal



```php
public getTotal(): mixed
```












***

### getPagination



```php
public getPagination(): mixed
```












***

### getSchoolyears



```php
public getSchoolyears(): mixed
```












***

### addGroupsByProgrammes



```php
public addGroupsByProgrammes(array $programmes): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programmes` | **array** | the programme newly added that should be affected to groups |


**Return Value:**


Add new groups




***

### getFabrikGroupsAssignedToEmundusGroups



```php
public getFabrikGroupsAssignedToEmundusGroups(mixed $group_ids): array|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group_ids` | **mixed** |  |





***


***
> Last updated on 20/08/2024
