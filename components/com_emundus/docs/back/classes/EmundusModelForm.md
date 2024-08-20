***

# EmundusModelForm





* Full name: `\EmundusModelForm`
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

## Methods


### __construct



```php
public __construct(mixed $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **mixed** |  |





***

### getAllForms



```php
public getAllForms(string $filter = &#039;&#039;, string $sort = &#039;&#039;, string $recherche = &#039;&#039;, int $lim, int $page): array|\stdClass
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filter` | **string** |  |
| `$sort` | **string** |  |
| `$recherche` | **string** |  |
| `$lim` | **int** |  |
| `$page` | **int** |  |





***

### getAllGrilleEval

TODO: Add filters / recherche etc./.. At the moment, it's not working

```php
public getAllGrilleEval(mixed $filter, mixed $sort, mixed $recherche, mixed $lim, mixed $page): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filter` | **mixed** |  |
| `$sort` | **mixed** |  |
| `$recherche` | **mixed** |  |
| `$lim` | **mixed** |  |
| `$page` | **mixed** |  |





***

### getAllFormsPublished



```php
public getAllFormsPublished(): mixed
```












***

### deleteForm



```php
public deleteForm(mixed $data): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |





***

### unpublishForm



```php
public unpublishForm(mixed $data): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |





***

### publishForm



```php
public publishForm(mixed $data): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |





***

### duplicateForm



```php
public duplicateForm(mixed $data, mixed $duplicate_condition = true): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |
| `$duplicate_condition` | **mixed** |  |





***

### copyAttachmentsToNewProfile



```php
public copyAttachmentsToNewProfile(mixed $oldprofile, mixed $newprofile): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$oldprofile` | **mixed** |  |
| `$newprofile` | **mixed** |  |





***

### getFormById



```php
public getFormById(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getFormByFabrikId



```php
public getFormByFabrikId(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### createApplicantProfile



```php
public createApplicantProfile(mixed $first_page = true): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$first_page` | **mixed** |  |





***

### createFormEval



```php
public createFormEval(mixed $user = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |





***

### createMenuType



```php
public createMenuType(mixed $menutype, mixed $title): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$menutype` | **mixed** |  |
| `$title` | **mixed** |  |





***

### createMenu



```php
public createMenu(mixed $menu, mixed $menutype): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$menu` | **mixed** |  |
| `$menutype` | **mixed** |  |





***

### updateForm



```php
public updateForm(mixed $id, mixed $data): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$data` | **mixed** |  |





***

### updateFormLabel



```php
public updateFormLabel(mixed $prid, mixed $label): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |
| `$label` | **mixed** |  |





***

### getAllDocuments



```php
public getAllDocuments(mixed $prid, mixed $cid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |
| `$cid` | **mixed** |  |





***

### getUnDocuments



```php
public getUnDocuments(): mixed
```












***

### getAttachments



```php
public getAttachments(): mixed
```












***

### getDocumentsUsage



```php
public getDocumentsUsage(mixed $documentIds): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$documentIds` | **mixed** |  |





***

### deleteRemainingDocuments



```php
public deleteRemainingDocuments(mixed $prid, mixed $allDocumentsIds): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |
| `$allDocumentsIds` | **mixed** |  |





***

### removeDocument



```php
public removeDocument(mixed $did, mixed $prid, mixed $cid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |
| `$prid` | **mixed** |  |
| `$cid` | **mixed** |  |





***

### updateMandatory



```php
public updateMandatory(mixed $did, mixed $prid, mixed $cid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |
| `$prid` | **mixed** |  |
| `$cid` | **mixed** |  |





***

### addDocument



```php
public addDocument(mixed $did, mixed $profile, mixed $campaign): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |
| `$profile` | **mixed** |  |
| `$campaign` | **mixed** |  |





***

### deleteDocument



```php
public deleteDocument(mixed $did): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |





***

### addChecklistMenu



```php
public addChecklistMenu(mixed $prid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |





***

### removeChecklistMenu



```php
public removeChecklistMenu(mixed $prid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |





***

### getFormsByProfileId



```php
public getFormsByProfileId(mixed $profile_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |





***

### getCampaignsByProfile



```php
public getCampaignsByProfile(mixed $profile_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |





***

### getGroupsByForm



```php
public getGroupsByForm(mixed $form_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id` | **mixed** |  |





***

### getSubmittionPage



```php
public getSubmittionPage(mixed $prid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |





***

### getProfileLabelByProfileId



```php
public getProfileLabelByProfileId(mixed $profile_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |





***

### getFilesByProfileId



```php
public getFilesByProfileId(mixed $profile_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |





***

### getAssociatedCampaign



```php
public getAssociatedCampaign(mixed $profile_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |





***

### getAssociatedProgram



```php
public getAssociatedProgram(mixed $form_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id` | **mixed** |  |





***

### affectCampaignsToForm



```php
public affectCampaignsToForm(mixed $prid, mixed $campaigns): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |
| `$campaigns` | **mixed** |  |





***

### getDocumentsByProfile



```php
public getDocumentsByProfile(mixed $prid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |





***

### reorderDocuments



```php
public reorderDocuments(mixed $documents): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$documents` | **mixed** |  |





***

### removeDocumentFromProfile



```php
public removeDocumentFromProfile(mixed $did): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |





***

### deleteModelDocument



```php
public deleteModelDocument(mixed $did): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |





***

### getDatabaseJoinOptions



```php
public getDatabaseJoinOptions(mixed $table, mixed $column, mixed $value, mixed $concat_value = null, mixed $where = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table` | **mixed** |  |
| `$column` | **mixed** |  |
| `$value` | **mixed** |  |
| `$concat_value` | **mixed** |  |
| `$where` | **mixed** |  |





***

### checkIfDocCanBeRemovedFromCampaign



```php
public checkIfDocCanBeRemovedFromCampaign(mixed $document_id, mixed $profile_id): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$document_id` | **mixed** |  |
| `$profile_id` | **mixed** |  |





***

### getProgramsByForm



```php
public getProgramsByForm(mixed $form_id, mixed $mode = &#039;eval&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id` | **mixed** |  |
| `$mode` | **mixed** |  |





***

### associateFabrikGroupsToProgram



```php
public associateFabrikGroupsToProgram(mixed $form_id, mixed $programs, mixed $mode = &#039;eval&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id` | **mixed** |  |
| `$programs` | **mixed** |  |
| `$mode` | **mixed** |  |





***

### getJSConditionsByForm



```php
public getJSConditionsByForm(mixed $form_id, mixed $format = &#039;raw&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id` | **mixed** |  |
| `$format` | **mixed** |  |





***

### addRule



```php
public addRule(mixed $form_id, mixed $grouped_conditions, mixed $actions, mixed $type = &#039;js&#039;, mixed $group = &#039;OR&#039;, mixed $label = &#039;&#039;, mixed $user = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id` | **mixed** |  |
| `$grouped_conditions` | **mixed** |  |
| `$actions` | **mixed** |  |
| `$type` | **mixed** |  |
| `$group` | **mixed** |  |
| `$label` | **mixed** |  |
| `$user` | **mixed** |  |





***

### editRule



```php
public editRule(mixed $rule_id, mixed $grouped_conditions, mixed $actions, mixed $group = &#039;OR&#039;, mixed $label = &#039;&#039;, mixed $user = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rule_id` | **mixed** |  |
| `$grouped_conditions` | **mixed** |  |
| `$actions` | **mixed** |  |
| `$group` | **mixed** |  |
| `$label` | **mixed** |  |
| `$user` | **mixed** |  |





***

### deleteRule



```php
public deleteRule(mixed $rule_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rule_id` | **mixed** |  |





***

### publishRule



```php
public publishRule(mixed $rule_id, mixed $state, mixed $user = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rule_id` | **mixed** |  |
| `$state` | **mixed** |  |
| `$user` | **mixed** |  |





***

### addCondition



```php
private addCondition(mixed $rule_id, mixed $condition): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rule_id` | **mixed** |  |
| `$condition` | **mixed** |  |





***

### createConditionGroup



```php
private createConditionGroup(mixed $group_type): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group_type` | **mixed** |  |





***

### addAction



```php
private addAction(mixed $rule_id, mixed $action): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rule_id` | **mixed** |  |
| `$action` | **mixed** |  |





***


***
> Automatically generated on 2024-08-20
