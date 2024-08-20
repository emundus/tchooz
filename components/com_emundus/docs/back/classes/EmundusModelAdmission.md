***

# EmundusModelAdmission





* Full name: `\EmundusModelAdmission`
* Parent class: [`JModelList`](./JModelList.md)



## Properties


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

### _applicants



```php
private $_applicants
```






***

### subquery



```php
private $subquery
```






***

### _elements_default



```php
private $_elements_default
```






***

### _elements



```php
private $_elements
```






***

### _files



```php
private $_files
```






***

### fnum_assoc



```php
public $fnum_assoc
```






***

### code



```php
public $code
```






***

## Methods


### __construct

Constructor

```php
public __construct(): mixed
```












***

### getElementsVar



```php
public getElementsVar(): mixed
```












***

### getAdmissionElementsName

Get list of admission elements

```php
public getAdmissionElementsName(int $show_in_list_summary = 1, int $hidden, null $code = null, mixed $all = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **int** |  |
| `$hidden` | **int** |  |
| `$code` | **null** |  |
| `$all` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in admission form



**Throws:**

- [`Exception`](./Exception.md)



***

### getApplicantAdmissionElementsName

Get list of admission elements

```php
public getApplicantAdmissionElementsName(mixed $show_in_list_summary = 1, mixed $hidden, mixed $code = null, mixed $all = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **mixed** |  |
| `$hidden` | **mixed** |  |
| `$code` | **mixed** |  |
| `$all` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in admission form
*@throws Exception




***

### getAllAdmissionElements

Get list of ALL admission elements

```php
public getAllAdmissionElements(mixed $show_in_list_summary, mixed $programme_code): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **mixed** |  |
| `$programme_code` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in admission form




***

### getAllApplicantAdmissionElements

Get list of ALL admission elements from applicant form

```php
public getAllApplicantAdmissionElements(mixed $show_in_list_summary, mixed $programme_code): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **mixed** |  |
| `$programme_code` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in admission form




***

### _buildContentOrderBy



```php
public _buildContentOrderBy(): mixed
```












***

### multi_array_sort



```php
public multi_array_sort(mixed $multi_array, mixed $sort_key, mixed $sort = SORT_ASC): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$multi_array` | **mixed** |  |
| `$sort_key` | **mixed** |  |
| `$sort` | **mixed** |  |





***

### getCampaign



```php
public getCampaign(): mixed
```












***

### getCurrentCampaign



```php
public getCurrentCampaign(): mixed
```












***

### getCurrentCampaignsID



```php
public getCurrentCampaignsID(): mixed
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

### setSubQuery



```php
public setSubQuery(mixed $tab, mixed $elem): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tab` | **mixed** |  |
| `$elem` | **mixed** |  |





***

### setSelect



```php
public setSelect(mixed $search): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$search` | **mixed** |  |





***

### isJoined



```php
public isJoined(mixed $tab, mixed $joined): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tab` | **mixed** |  |
| `$joined` | **mixed** |  |





***

### setJoins



```php
public setJoins(mixed $search, mixed $query, mixed $joined): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$search` | **mixed** |  |
| `$query` | **mixed** |  |
| `$joined` | **mixed** |  |





***

### _buildSelect



```php
public _buildSelect(mixed& $tables_list, mixed& $tables_list_other, mixed& $tables_list_default): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tables_list` | **mixed** |  |
| `$tables_list_other` | **mixed** |  |
| `$tables_list_default` | **mixed** |  |





***

### setEvalList



```php
public setEvalList(array $search, array& $eval_list, array $head_val, object $applicant): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$search` | **array** | filters elements |
| `$eval_list` | **array** | reference of result list |
| `$head_val` | **array** | header name |
| `$applicant` | **object** | array of applicants indexed by database column |





***

### _buildWhere



```php
private _buildWhere(mixed $tableAlias = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tableAlias` | **mixed** |  |





***

### getUsers



```php
public getUsers(mixed $current_fnum = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$current_fnum` | **mixed** |  |





***

### getElementsByGroups



```php
public getElementsByGroups(mixed $groups, mixed $show_in_list_summary = 1, mixed $hidden): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groups` | **mixed** |  |
| `$show_in_list_summary` | **mixed** |  |
| `$hidden` | **mixed** |  |





***

### getAllElementsByGroups



```php
public getAllElementsByGroups(mixed $groups, mixed $show_in_list_summary = null, mixed $hidden = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groups` | **mixed** |  |
| `$show_in_list_summary` | **mixed** |  |
| `$hidden` | **mixed** |  |





***

### getDefaultElements



```php
public getDefaultElements(): mixed
```












***

### getSelectList



```php
public getSelectList(): mixed
```












***

### getProfiles



```php
public getProfiles(): mixed
```












***

### getProfileByID



```php
public getProfileByID(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





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

### getElementsName



```php
public getElementsName(): mixed
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

### getPageNavigation



```php
public getPageNavigation(): string
```












***

### getApplicantColumns



```php
public getApplicantColumns(): mixed
```












***

### getGroupsAdmissionByProgramme



```php
public getGroupsAdmissionByProgramme(mixed $code): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getGroupsApplicantAdmissionByProgramme



```php
public getGroupsApplicantAdmissionByProgramme(mixed $code): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getSchoolyears



```php
public getSchoolyears(): mixed
```












***

### getAllActions



```php
public getAllActions(): mixed
```












***

### getEvalGroups



```php
public getEvalGroups(): mixed
```












***

### shareGroups



```php
public shareGroups(mixed $groups, mixed $actions, mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groups` | **mixed** |  |
| `$actions` | **mixed** |  |
| `$fnums` | **mixed** |  |





***

### shareUsers



```php
public shareUsers(mixed $users, mixed $actions, mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$actions` | **mixed** |  |
| `$fnums` | **mixed** |  |





***

### getAllTags



```php
public getAllTags(): mixed
```












***

### getAllStatus



```php
public getAllStatus(): mixed
```












***

### tagFile



```php
public tagFile(mixed $fnums, mixed $tag): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$tag` | **mixed** |  |





***

### getTaggedFile



```php
public getTaggedFile(mixed $tag = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |





***

### updateState



```php
public updateState(mixed $fnums, mixed $state): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$state` | **mixed** |  |





***

### getPhotos



```php
public getPhotos(): mixed
```












***

### getEvaluatorsFromGroup



```php
public getEvaluatorsFromGroup(): mixed
```












***

### getEvaluators



```php
public getEvaluators(): mixed
```












***

### unlinkEvaluators



```php
public unlinkEvaluators(mixed $fnum, mixed $id, mixed $isGroup): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$id` | **mixed** |  |
| `$isGroup` | **mixed** |  |





***

### getFnumInfos



```php
public getFnumInfos(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### changePublished



```php
public changePublished(mixed $fnum, mixed $published = -1): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$published` | **mixed** |  |





***

### getAllFnums



```php
public getAllFnums(): mixed
```












***

### getFnumArray



```php
public getFnumArray(mixed $fnums, mixed $elements): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$elements` | **mixed** |  |





***

### getEvalsByFnum



```php
public getEvalsByFnum(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getCommentsByFnum



```php
public getCommentsByFnum(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getFilesByFnums



```php
public getFilesByFnums(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getAdmissionFnum



```php
public getAdmissionFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getAdmissionFormByProgramme



```php
public getAdmissionFormByProgramme(mixed $code = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getAdmissionId



```php
public getAdmissionId(mixed $form_table, mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_table` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### setAdmissionByFabrikElementsId



```php
public setAdmissionByFabrikElementsId(mixed $fnum, mixed $element_id, mixed $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$element_id` | **mixed** |  |
| `$value` | **mixed** |  |





***

### updateAdmissionByFabrikElementsId



```php
public updateAdmissionByFabrikElementsId(mixed $fnum, mixed $element_id, mixed $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$element_id` | **mixed** |  |
| `$value` | **mixed** |  |





***

### getAdmissionInfo



```php
public getAdmissionInfo(mixed $sid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sid` | **mixed** |  |





***


***
> Last updated on 20/08/2024
