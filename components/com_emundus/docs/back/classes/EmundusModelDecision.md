***

# EmundusModelDecision





* Full name: `\EmundusModelDecision`
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

### getDecisionElementsName

Get list of decision elements

```php
public getDecisionElementsName(mixed $show_in_list_summary = 1, mixed $hidden, mixed $code = array(), mixed $all = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **mixed** |  |
| `$hidden` | **mixed** |  |
| `$code` | **mixed** |  |
| `$all` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in decision form
*@throws Exception




***

### getAllDecisionElements

Get list of ALL decision elements

```php
public getAllDecisionElements(mixed $show_in_list_summary, mixed $programme_code): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **mixed** |  |
| `$programme_code` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in evaluation form



**Throws:**

- [`Exception`](./Exception.md)



***

### _buildContentOrderBy



```php
public _buildContentOrderBy(): string
```











**Throws:**

- [`Exception`](./Exception.md)



***

### multi_array_sort



```php
public multi_array_sort(array $multi_array = array(), mixed $sort_key, int $sort = SORT_ASC): array|int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$multi_array` | **array** |  |
| `$sort_key` | **mixed** |  |
| `$sort` | **int** |  |





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
public setSubQuery(mixed $tab, mixed $elem): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tab` | **mixed** |  |
| `$elem` | **mixed** |  |





***

### setSelect



```php
public setSelect(mixed $search): array|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$search` | **mixed** |  |





***

### isJoined



```php
public isJoined(mixed $tab, mixed $joined): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tab` | **mixed** |  |
| `$joined` | **mixed** |  |





***

### setJoins



```php
public setJoins(mixed $search, mixed $query, mixed $joined): array
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
public _buildSelect(mixed& $tables_list, mixed& $tables_list_other, mixed& $tables_list_default): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tables_list` | **mixed** |  |
| `$tables_list_other` | **mixed** |  |
| `$tables_list_default` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



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
private _buildWhere(array $tableAlias = array()): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tableAlias` | **array** |  |





***

### getUsers



```php
public getUsers(null $current_fnum = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$current_fnum` | **null** |  |




**Throws:**

- [`Exception`](./Exception.md)



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
public getAllElementsByGroups(mixed $groups): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groups` | **mixed** |  |





***

### getActionsACL



```php
public getActionsACL(): mixed
```












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

### getGroupsEvalByProgramme



```php
public getGroupsEvalByProgramme(mixed $code): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getGroupsDecisionByProgramme



```php
public getGroupsDecisionByProgramme(mixed $code): mixed
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

### getExperts



```php
public getExperts(mixed $fnum, mixed $select, mixed $table): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$select` | **mixed** |  |
| `$table` | **mixed** |  |





***

### getEvaluationsFnum



```php
public getEvaluationsFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





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

### getDecisionFnum



```php
public getDecisionFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getLettersTemplate



```php
public getLettersTemplate(mixed $eligibility, mixed $training): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eligibility` | **mixed** |  |
| `$training` | **mixed** |  |





***

### getLettersTemplateByID



```php
public getLettersTemplateByID(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getEvaluationFormByProgramme



```php
public getEvaluationFormByProgramme(mixed $code = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getDecisionFormByProgramme



```php
public getDecisionFormByProgramme(mixed $code = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getDecisionUrl



```php
public getDecisionUrl(mixed $fnum, mixed $formid, mixed $rowid, mixed $student_id, mixed $redirect, mixed $view = &#039;form&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$formid` | **mixed** |  |
| `$rowid` | **mixed** |  |
| `$student_id` | **mixed** |  |
| `$redirect` | **mixed** |  |
| `$view` | **mixed** |  |





***


***
> Automatically generated on 2024-08-19
