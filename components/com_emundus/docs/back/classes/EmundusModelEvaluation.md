***

# EmundusModelEvaluation





* Full name: `\EmundusModelEvaluation`
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

### use_module_filters



```php
private $use_module_filters
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

### getEvaluationElements

Get list of evaluation element

```php
public getEvaluationElements(mixed $show_in_list_summary = 1, mixed $hidden): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **mixed** |  |
| `$hidden` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in evaluation form
*@throws Exception




***

### getEvaluationElementsName

Get list of evaluation elements

```php
public getEvaluationElementsName(mixed $show_in_list_summary = 1, mixed $hidden, mixed $code = array(), mixed $all = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **mixed** |  |
| `$hidden` | **mixed** |  |
| `$code` | **mixed** |  |
| `$all` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in evaluation form
*@throws Exception




***

### getAllEvaluationElements

Get list of ALL evaluation element

```php
public getAllEvaluationElements(int $show_in_list_summary, mixed $programme_code): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$show_in_list_summary` | **int** | 1 is default value |
| `$programme_code` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in evaluation form



**Throws:**

- [`Exception`](./Exception.md)



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
*@throws Exception




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
private _buildWhere(mixed $already_joined_tables = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$already_joined_tables` | **mixed** |  |





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
public getExperts(): mixed
```












***

### getEvaluationDocuments

Get list of documents generated for email attachment

```php
public getEvaluationDocuments(mixed $fnum, mixed $campaign_id, int|null $doc_to_attach = null): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$campaign_id` | **mixed** |  |
| `$doc_to_attach` | **int&#124;null** |  |





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

Get evaluations for fnum done by a user

```php
public getEvaluationsFnumUser(mixed $fnum, mixed $user): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$user` | **mixed** |  |





***

### getEvaluationsByStudent

Get all evaluations accross all programs for a student

```php
public getEvaluationsByStudent(mixed $user): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |





***

### getEvaluationsByFnum

Get all evaluations accross all programs for a student application file

```php
public getEvaluationsByFnum(mixed $fnum): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### copyEvaluation

Copy a line by ID from the evaluation table and use it to overrite or create another line

```php
public copyEvaluation(mixed $fromID, mixed $toID = null, mixed $fnum = null, mixed $student = null, mixed $user = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fromID` | **mixed** | line to copy data from |
| `$toID` | **mixed** | line to copy data to |
| `$fnum` | **mixed** |  |
| `$student` | **mixed** |  |
| `$user` | **mixed** |  |





***

### getDecisionFnum

Get evaluations for fnum done by a user

```php
public getDecisionFnum(mixed $fnum): array
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
public getLettersTemplateByID(mixed $id = null): mixed
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

### getEvaluationAverageByFnum



```php
public getEvaluationAverageByFnum(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getEvalsByFnums



```php
public getEvalsByFnums(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### delevaluation



```php
public delevaluation(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getEvaluationById



```php
public getEvaluationById(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getScore



```php
public getScore(mixed $fnum): array|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getAttachmentByIds



```php
public getAttachmentByIds(array $ids): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **array** |  |





***

### getLettersByFnums



```php
public getLettersByFnums(mixed $fnums, mixed $attachments = false): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$attachments` | **mixed** |  |





***

### getLettersByProgrammesStatusCampaigns



```php
public getLettersByProgrammesStatusCampaigns(mixed $programs = array(), mixed $status = array(), mixed $campaigns = array()): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programs` | **mixed** |  |
| `$status` | **mixed** |  |
| `$campaigns` | **mixed** |  |





***

### getLetterTemplateForFnum



```php
public getLetterTemplateForFnum(mixed $fnum, mixed $templates = array()): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$templates` | **mixed** |  |





***

### getLettersByFnumsTemplates



```php
public getLettersByFnumsTemplates(mixed $fnums = array(), mixed $templates = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$templates` | **mixed** |  |





***

### getFilesByAttachmentFnums



```php
public getFilesByAttachmentFnums(mixed $attachment, mixed $fnums = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attachment` | **mixed** |  |
| `$fnums` | **mixed** |  |





***

### generateLetters



```php
public generateLetters(mixed $fnums, mixed $templates, mixed $canSee, mixed $showMode, mixed $mergeMode): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$templates` | **mixed** |  |
| `$canSee` | **mixed** |  |
| `$showMode` | **mixed** |  |
| `$mergeMode` | **mixed** |  |





***

### ZipLetter



```php
public ZipLetter(mixed $source, mixed $destination, mixed $include_dir = false): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$source` | **mixed** |  |
| `$destination` | **mixed** |  |
| `$include_dir` | **mixed** |  |





***

### sanitize_filename



```php
private sanitize_filename(mixed $name): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **mixed** |  |





***

### copy_directory



```php
private copy_directory(mixed $src, mixed $dst): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$src` | **mixed** |  |
| `$dst` | **mixed** |  |





***

### deleteAll



```php
private deleteAll(mixed $dir): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$dir` | **mixed** |  |





***

### getMyEvaluations



```php
public getMyEvaluations(mixed $user, mixed $campaign, mixed $module): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |
| `$campaign` | **mixed** |  |
| `$module` | **mixed** |  |





***

### getCampaignsToEvaluate



```php
public getCampaignsToEvaluate(mixed $user, mixed $module): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |
| `$module` | **mixed** |  |





***

### getEvaluationUrl



```php
public getEvaluationUrl(mixed $fnum, mixed $formid, mixed $rowid, mixed $student_id, mixed $redirect, mixed $view = &#039;form&#039;): mixed
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

### getRowByFnum



```php
public getRowByFnum(mixed $fnum, mixed $table_name): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$table_name` | **mixed** |  |





***

### getEvaluationReasons



```php
public getEvaluationReasons(mixed $eid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |





***


***
> Automatically generated on 2024-08-19
