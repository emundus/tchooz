***

# EmundusModelProgramme





* Full name: `\EmundusModelProgramme`
* Parent class: [`ListModel`](./Joomla/CMS/MVC/Model/ListModel.md)



## Properties


### app



```php
private $app
```






***

### _em_user



```php
private $_em_user
```






***

### _user



```php
private $_user
```






***

### _db



```php
protected $_db
```






***

### config



```php
private $config
```






***

## Methods


### __construct



```php
public __construct(): mixed
```












***

### getCampaign

Method to get article data.

```php
public getCampaign(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |


**Return Value:**

Menu item data object on success, false on failure.




***

### getParams



```php
public getParams(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getAssociatedProgrammes



```php
public getAssociatedProgrammes(mixed $user): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |


**Return Value:**


get list of programmes for associated files




***

### getProgrammes



```php
public getProgrammes(mixed $published = null, mixed $codeList = array()): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$published` | **mixed** | int     get published or unpublished programme |
| `$codeList` | **mixed** | array   array of IN and NOT IN programme code to get |





***

### getProgramme



```php
public getProgramme(mixed $code): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |


**Return Value:**


get list of declared programmes




***

### addProgrammes



```php
public addProgrammes(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to add in table. |


**Return Value:**


Add new programme in DB




***

### editProgrammes



```php
public editProgrammes(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to add in table. |


**Return Value:**


Edit programme in DB




***

### getLatestProgramme

Gets the most recent programme code.

```php
public getLatestProgramme(): string
```









**Return Value:**

The most recently added programme in the DB.




***

### isFavorite

Checks if the user has this programme in his favorites.

```php
public isFavorite(mixed $programme_id, null $user_id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programme_id` | **mixed** | Int The ID of the programme to be favorited. |
| `$user_id` | **null** | Int The user ID, if null: the current user ID. |


**Return Value:**

True if favorited.




***

### favorite

Adds a programme to the user's list of favorites.

```php
public favorite(mixed $programme_id, null $user_id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programme_id` | **mixed** | Int The ID of the programme to be favorited. |
| `$user_id` | **null** | Int The user ID, if null: the current user ID. |





***

### unfavorite

Removes a programme from the user's list of favorites.

```php
public unfavorite(mixed $programme_id, null $user_id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programme_id` | **mixed** | Int The ID of the programme to be unfavorited. |
| `$user_id` | **null** | Int The user ID, if null: the current user ID. |





***

### getUpcomingFavorites

Get's the upcoming sessions of the user's favorite programs.

```php
public getUpcomingFavorites(null $user_id = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **null** |  |





***

### getFavorites

Get's the user's favorite programs.

```php
public getFavorites(null $user_id = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **null** |  |





***

### getAllPrograms



```php
public getAllPrograms(mixed $lim = &#039;all&#039;, mixed $page, mixed $filter = null, mixed $sort = &#039;DESC&#039;, mixed $recherche = null, mixed $user = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lim` | **mixed** |  |
| `$page` | **mixed** |  |
| `$filter` | **mixed** |  |
| `$sort` | **mixed** |  |
| `$recherche` | **mixed** |  |
| `$user` | **mixed** |  |





***

### getProgramCount



```php
public getProgramCount(mixed $filter, mixed $recherche): int|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filter` | **mixed** |  |
| `$recherche` | **mixed** |  |





***

### getProgramById



```php
public getProgramById(mixed $id): false|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### addProgram



```php
public addProgram(mixed $data, mixed $user = null): false|mixed|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |
| `$user` | **mixed** |  |





***

### updateProgram



```php
public updateProgram(int $id, array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **int** | the program to update |
| `$data` | **array** | the row to add in table. |


**Return Value:**


Update program in DB




***

### deleteProgram



```php
public deleteProgram(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to delete in table. |


**Return Value:**


Delete program(s) in DB




***

### unpublishProgram



```php
public unpublishProgram(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to unpublish in table. |


**Return Value:**


Unpublish program(s) in DB




***

### publishProgram



```php
public publishProgram(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to publish in table. |


**Return Value:**


Publish program(s) in DB




***

### getProgramCategories



```php
public getProgramCategories(): array
```









**Return Value:**


get list of declared programmes




***

### getYearsByProgram

get list of all campaigns associated to the user

```php
public getYearsByProgram(mixed $code): object
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getManagers



```php
public getManagers(mixed $group): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |





***

### getEvaluators



```php
public getEvaluators(mixed $group): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |





***

### affectusertogroups



```php
public affectusertogroups(mixed $group, mixed $email, mixed $prog_group): false|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$email` | **mixed** |  |
| `$prog_group` | **mixed** |  |





***

### affectuserstogroup



```php
public affectuserstogroup(mixed $group, mixed $users, mixed $prog_group): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$users` | **mixed** |  |
| `$prog_group` | **mixed** |  |





***

### removefromgroup



```php
public removefromgroup(mixed $userid, mixed $group, mixed $prog_group): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$userid` | **mixed** |  |
| `$group` | **mixed** |  |
| `$prog_group` | **mixed** |  |





***

### getusers



```php
public getusers(mixed $filters, mixed $page = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filters` | **mixed** |  |
| `$page` | **mixed** |  |





***

### updateVisibility



```php
public updateVisibility(mixed $cid, mixed $gid, mixed $visibility): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |
| `$gid` | **mixed** |  |
| `$visibility` | **mixed** |  |





***

### clonegroup



```php
public clonegroup(mixed $gid): mixed|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### getEvaluationGrid



```php
public getEvaluationGrid(mixed $pid): false|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** |  |





***

### affectGroupToProgram



```php
public affectGroupToProgram(mixed $group, mixed $pid): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$pid` | **mixed** |  |





***

### deleteGroupFromProgram



```php
public deleteGroupFromProgram(mixed $group, mixed $pid): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$pid` | **mixed** |  |





***

### createGridFromModel



```php
public createGridFromModel(mixed $label, mixed $intro, mixed $model, mixed $pid): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$intro` | **mixed** |  |
| `$model` | **mixed** |  |
| `$pid` | **mixed** |  |





***

### getGridsModel



```php
public getGridsModel(): array|false|mixed
```












***

### createGrid



```php
public createGrid(mixed $label, mixed $intro, mixed $pid, mixed $template): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$intro` | **mixed** |  |
| `$pid` | **mixed** |  |
| `$template` | **mixed** |  |





***

### deleteGrid



```php
public deleteGrid(mixed $grid, mixed $pid): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$grid` | **mixed** |  |
| `$pid` | **mixed** |  |





***

### getUserPrograms



```php
public getUserPrograms(mixed $user_id): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getGroupsByPrograms



```php
public getGroupsByPrograms(mixed $programs): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programs` | **mixed** |  |





***

### addGroupToProgram



```php
public addGroupToProgram(mixed $label, mixed $code, mixed $parent): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$code` | **mixed** |  |
| `$parent` | **mixed** |  |





***

### getGroupByParent



```php
public getGroupByParent(mixed $code, mixed $parent): false|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |
| `$parent` | **mixed** |  |





***

### getCampaignsByProgram



```php
public getCampaignsByProgram(mixed $program): array|false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$program` | **mixed** |  |





***

### getAllSessions



```php
public getAllSessions(): mixed
```












***


***
> Automatically generated on 2024-08-19
