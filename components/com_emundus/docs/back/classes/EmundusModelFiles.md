***

# EmundusModelFiles

Class EmundusModelFiles



* Full name: `\EmundusModelFiles`
* Parent class: [`JModelLegacy`](./JModelLegacy.md)



## Properties


### app



```php
private $app
```






***

### _db



```php
protected $_db
```






***

### _total



```php
private null $_total
```






***

### _pagination



```php
private null $_pagination
```






***

### _applicants



```php
private array $_applicants
```






***

### subquery



```php
private array $subquery
```






***

### _elements_default



```php
private array $_elements_default
```






***

### _elements



```php
private array $_elements
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

### use_module_filters



```php
public $use_module_filters
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
public getElementsVar(): array
```












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
public multi_array_sort(array $multi_array, mixed $sort_key, int $sort = SORT_ASC): array|int
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











**Throws:**

- [`Exception`](./Exception.md)



***

### getCurrentCampaignsID



```php
public getCurrentCampaignsID(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



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
private _buildWhere(mixed $already_joined_tables = array()): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$already_joined_tables` | **mixed** |  |





***

### getUsers



```php
public getUsers(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getAllUsers



```php
public getAllUsers(mixed $limitStart, mixed $limit = 20): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$limitStart` | **mixed** | int     request start |
| `$limit` | **mixed** | int     request limit |




**Throws:**

- [`Exception`](./Exception.md)



***

### getUserGroups



```php
public getUserGroups(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getDefaultElements



```php
public getDefaultElements(): array
```












***

### getSelectList



```php
public getSelectList(): array|string
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
public getTotal(): int|null
```












***

### getApplicantColumns



```php
public getApplicantColumns(): array
```












***

### getPagination



```php
public getPagination(): \JPagination|null
```












***

### getPageNavigation



```php
public getPageNavigation(): string
```












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











**Throws:**

- [`Exception`](./Exception.md)



***

### getEvalGroups



```php
public getEvalGroups(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### shareGroups



```php
public shareGroups(mixed $groups, mixed $actions, mixed $fnums): bool|mixed
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
public shareUsers(mixed $users, mixed $actions, mixed $fnums, mixed $current_user = null): bool|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$actions` | **mixed** |  |
| `$fnums` | **mixed** |  |
| `$current_user` | **mixed** |  |





***

### getAllTags



```php
public getAllTags(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getAllGroups



```php
public getAllGroups(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getUserAssocGroups

Gets the groups the user is a part of OR if the user has read access on groups, all groups.

```php
public getUserAssocGroups(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getAllInstitutions



```php
public getAllInstitutions(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getAllStatus



```php
public getAllStatus(mixed $uid = null, mixed $result_index = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |
| `$result_index` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getStatusByID



```php
public getStatusByID(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getStatusByFnums



```php
public getStatusByFnums(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### tagFile



```php
public tagFile(mixed $fnums, mixed $tags, mixed $user = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$tags` | **mixed** |  |
| `$user` | **mixed** |  |





***

### getTaggedFile



```php
public getTaggedFile(null $tag = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **null** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### updateState



```php
public updateState(mixed $fnums, mixed $state, mixed $user_id = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$state` | **mixed** |  |
| `$user_id` | **mixed** |  |





***

### updatePublish



```php
public updatePublish(mixed $fnums, mixed $publish, mixed $user_id = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$publish` | **mixed** |  |
| `$user_id` | **mixed** |  |





***

### getPhotos



```php
public getPhotos(array $fnums = array()): mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **array** |  |





***

### getEvaluatorsFromGroup



```php
public getEvaluatorsFromGroup(): mixed|null
```












***

### getEvaluators



```php
public getEvaluators(): mixed|null
```












***

### unlinkEvaluators



```php
public unlinkEvaluators(mixed $fnum, mixed $id, mixed $isGroup): bool|mixed
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
public getFnumInfos(mixed $fnum, mixed $user_id): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$user_id` | **mixed** |  |





***

### getFnumsInfos



```php
public getFnumsInfos(mixed $fnums, mixed $format = &#039;array&#039;): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$format` | **mixed** |  |





***

### getFnumsTagsInfos



```php
public getFnumsTagsInfos(mixed $fnums): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getFnumTagsInfos

Gets info for Fabrik tags.

```php
public getFnumTagsInfos(string $fnum): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **string** |  |





***

### getApplicantIdByFnum

Gets applicant_id from fnum

```php
public getApplicantIdByFnum(string $fnum): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **string** |  |





***

### changePublished



```php
public changePublished(mixed $fnum, int $published = -1): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$published` | **int** |  |





***

### getAllFnums



```php
public getAllFnums(mixed $assoc_tab_fnums = false, mixed $user_id = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$assoc_tab_fnums` | **mixed** |  |
| `$user_id` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getFnumArray



```php
public getFnumArray(mixed $fnums, mixed $elements, mixed $methode, mixed $start, mixed $pas, mixed $raw, mixed $defaultElement = &#039;&#039;, mixed $user = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$elements` | **mixed** |  |
| `$methode` | **mixed** |  |
| `$start` | **mixed** |  |
| `$pas` | **mixed** |  |
| `$raw` | **mixed** |  |
| `$defaultElement` | **mixed** |  |
| `$user` | **mixed** |  |





***

### getFnumArray2



```php
public getFnumArray2(mixed $fnums, mixed $elements, mixed $start, mixed $limit, mixed $method, mixed $user_id = null): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$elements` | **mixed** |  |
| `$start` | **mixed** |  |
| `$limit` | **mixed** |  |
| `$method` | **mixed** | (0 : regroup all repeat elements in one row, and make values unique ; 1 : Don&#039;t regroup repeat elements, make a line for each repeat element ; 2 : regroup all repeat elements in one row, but write all values even if there are duplicates) |
| `$user_id` | **mixed** |  |





***

### getEvalsByFnum



```php
public getEvalsByFnum(mixed $fnums): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getEvalByFnum



```php
public getEvalByFnum(mixed $fnum): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getEvalByFnumAndEvaluator

Gets the evaluation of a user based on fnum and

```php
public getEvalByFnumAndEvaluator(mixed $fnum, mixed $evaluator_id): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$evaluator_id` | **mixed** |  |





***

### getCommentsByFnum



```php
public getCommentsByFnum(mixed $fnums): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getFilesByFnums



```php
public getFilesByFnums(mixed $fnums, mixed $attachment_ids = null, mixed $return_as_object = false): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$attachment_ids` | **mixed** |  |
| `$return_as_object` | **mixed** |  |





***

### getGroupsByFnums



```php
public getGroupsByFnums(mixed $fnums): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getAssessorsByFnums



```php
public getAssessorsByFnums(mixed $fnums, mixed $column = &#039;uname&#039;): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$column` | **mixed** |  |





***

### getAssociatedProgrammes



```php
public getAssociatedProgrammes(mixed $user): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |


**Return Value:**


get list of programmes for associated files




***

### getGroupsAssociatedProgrammes



```php
public getGroupsAssociatedProgrammes(mixed $user): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |


**Return Value:**


get list of programmes for groups associated files




***

### getMenuList



```php
public getMenuList(mixed $params): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### getFormidByFnum



```php
public getFormidByFnum(mixed $fnum): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getDecisionFormidByFnum



```php
public getDecisionFormidByFnum(mixed $fnum): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getAdmissionFormidByFnum



```php
public getAdmissionFormidByFnum(mixed $fnum): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getFormByFnum



```php
public getFormByFnum(mixed $fnum): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getAccessorByFnums



```php
public getAccessorByFnums(mixed $fnums): array|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getAssocByFnums

Gets the associated groups, users, or both, for an array of fnums. Used for XLS exports.

```php
public getAssocByFnums(mixed $fnums, bool $groups = true, bool $users = true): array|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$groups` | **bool** | Should we get associated groups ? |
| `$users` | **bool** |  |





***

### getTagsByFnum



```php
public getTagsByFnum(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getTagsByIdFnumUser



```php
public getTagsByIdFnumUser(mixed $tid, mixed $fnum, mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tid` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$user_id` | **mixed** |  |





***

### getProgByFnums



```php
public getProgByFnums(mixed $fnums): \Exception|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getDocsByProg



```php
public getDocsByProg(mixed $code): \Exception|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getAttachmentInfos



```php
public getAttachmentInfos(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### addAttachment



```php
public addAttachment(mixed $fnum, mixed $name, mixed $uid, mixed $cid, mixed $attachment_id, mixed $desc, mixed $canSee): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$name` | **mixed** |  |
| `$uid` | **mixed** |  |
| `$cid` | **mixed** |  |
| `$attachment_id` | **mixed** |  |
| `$desc` | **mixed** |  |
| `$canSee` | **mixed** |  |





***

### checkFnumsDoc



```php
public checkFnumsDoc(mixed $code, mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |
| `$fnums` | **mixed** |  |





***

### getAttachmentsById



```php
public getAttachmentsById(mixed $ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getSetupAttachmentsById



```php
public getSetupAttachmentsById(mixed $ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |





***

### getValueFabrikByIds



```php
public getValueFabrikByIds(mixed $idFabrik): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$idFabrik` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getVariables

Find all variables like ${var} or [var] in string.

```php
public getVariables(string $str, int $type = &#039;CURLY&#039;): string[]
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$str` | **string** |  |
| `$type` | **int** | type of bracket default CURLY else SQUARE |





***

### dateFormatToMysql

Return a date format from php to MySQL.

```php
public dateFormatToMysql(string $date_format): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date_format` | **string** |  |





***

### getFabrikValueRepeat



```php
public getFabrikValueRepeat(mixed $elt, null $fnums, mixed $params, mixed $groupRepeat): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elt` | **mixed** |  |
| `$fnums` | **null** |  |
| `$params` | **mixed** |  |
| `$groupRepeat` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getFabrikValue



```php
public getFabrikValue(mixed $fnums, mixed $tableName, mixed $name, mixed $dateFormat = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$tableName` | **mixed** |  |
| `$name` | **mixed** |  |
| `$dateFormat` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getStatus



```php
public getStatus(mixed $user_id = null): array|mixed
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### deleteFile



```php
public deleteFile(mixed $fnum): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### programSessions



```php
public programSessions(mixed $program): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$program` | **mixed** |  |





***

### getAppliedSessions



```php
public getAppliedSessions(mixed $program): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$program` | **mixed** |  |





***

### getBirthdate

Gets the user's birthdate.

```php
public getBirthdate(null $fnum = null, string $format = &#039;d-m-Y&#039;, bool $age = false): null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **null** | The file number to get the birth date from. |
| `$format` | **string** | See php.net/date |
| `$age` | **bool** | If true then we also return the current age. |





***

### getDocumentCategory



```php
public getDocumentCategory(): mixed
```












***

### getParamsCategory



```php
public getParamsCategory(mixed $idCategory): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$idCategory` | **mixed** |  |





***

### getAttachmentCategories

Gets the category names for the different attachment types.

```php
public getAttachmentCategories(): mixed
```












***

### selectCity



```php
public selectCity(mixed $insee): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$insee` | **mixed** |  |





***

### selectNameCity



```php
public selectNameCity(mixed $name): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **mixed** |  |





***

### selectMultiplePayment



```php
public selectMultiplePayment(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getAttachmentsAssignedToEmundusGroups



```php
public getAttachmentsAssignedToEmundusGroups(mixed $group_ids): array|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group_ids` | **mixed** |  |





***

### getFormProgress



```php
public getFormProgress(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getAttachmentProgress



```php
public getAttachmentProgress(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getUnreadMessages



```php
public getUnreadMessages(): mixed
```












***

### getTagsAssocStatus



```php
public getTagsAssocStatus(mixed $status): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$status` | **mixed** |  |





***

### checkIfSomeoneElseIsEditing



```php
public checkIfSomeoneElseIsEditing(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getStatusByStep



```php
public getStatusByStep(mixed $step): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$step` | **mixed** |  |





***

### getAllLogActions



```php
public getAllLogActions(): mixed
```












***

### bindFilesToUser

Copy given fnums and all data with it to another user

```php
public bindFilesToUser(mixed $fnums, mixed $user_to): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$user_to` | **mixed** |  |





***

### createFile

Create file for applicant

```php
public createFile(mixed $campaign_id, mixed $user_id, mixed $time = null): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **mixed** |  |
| `$user_id` | **mixed** | If not given, default to Current User |
| `$time` | **mixed** |  |





***

### sendEmailAfterUpdateState



```php
private sendEmailAfterUpdateState(mixed $fnums, mixed $state): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$state` | **mixed** |  |





***

### saveFilters



```php
public saveFilters(mixed $user_id, mixed $name, mixed $filters, mixed $item_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$name` | **mixed** |  |
| `$filters` | **mixed** |  |
| `$item_id` | **mixed** |  |





***

### getSavedFilters



```php
public getSavedFilters(mixed $user_id, mixed $item_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$item_id` | **mixed** |  |





***

### updateFilter



```php
public updateFilter(mixed $user_id, mixed $filter_id, mixed $filters, mixed $item_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$filter_id` | **mixed** |  |
| `$filters` | **mixed** |  |
| `$item_id` | **mixed** |  |





***

### getStatusByGroup



```php
public getStatusByGroup(mixed $uid = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### exportZip



```php
public exportZip(mixed $fnums, mixed $form_post = 1, mixed $attachment = 1, mixed $assessment = 1, mixed $decision = 1, mixed $admission = 1, mixed $form_ids = null, mixed $attachids = null, mixed $options = null, mixed $acl_override = false, mixed $current_user = null, mixed $params = []): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$form_post` | **mixed** |  |
| `$attachment` | **mixed** |  |
| `$assessment` | **mixed** |  |
| `$decision` | **mixed** |  |
| `$admission` | **mixed** |  |
| `$form_ids` | **mixed** |  |
| `$attachids` | **mixed** |  |
| `$options` | **mixed** |  |
| `$acl_override` | **mixed** |  |
| `$current_user` | **mixed** |  |
| `$params` | **mixed** |  |





***

### makeAttachmentsEditableByApplicant



```php
public makeAttachmentsEditableByApplicant(mixed $fnums, mixed $state): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$state` | **mixed** |  |





***


***
> Automatically generated on 2024-08-02
