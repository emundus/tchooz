***

# EmundusModelCampaign





* Full name: `\EmundusModelCampaign`
* Parent class: [`JModelList`](./JModelList.md)



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

### getActiveCampaign

Get active campaign

```php
public getActiveCampaign(): mixed
```












***

### _buildQuery

Build query to get campaign

```php
public _buildQuery(): string
```












***

### _buildContentOrderBy

Build Content with order by

```php
public _buildContentOrderBy(): string
```












***

### getAllowedCampaign

Get allowed campaigns by user and depending of eMundus params

```php
public getAllowedCampaign(mixed $uid = null): array|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getMyCampaign

Get campaigns by my applicant_id

```php
public getMyCampaign(): mixed
```












***

### getCampaignByID



```php
public getCampaignByID(mixed $campaign_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **mixed** |  |





***

### getAllCampaigns



```php
public getAllCampaigns(bool $published = true): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$published` | **bool** |  |





***

### getProgrammeByCampaignID



```php
public getProgrammeByCampaignID(mixed $campaign_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **mixed** |  |





***

### getProgrammeByTraining



```php
public getProgrammeByTraining(mixed $training): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$training` | **mixed** |  |





***

### getCampaignsByCourse



```php
public getCampaignsByCourse(mixed $course): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$course` | **mixed** |  |





***

### getCampaignsByProgram



```php
public getCampaignsByProgram(mixed $code): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getCampaignsByCourseCampaign



```php
public getCampaignsByCourseCampaign(mixed $course, mixed $camp): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$course` | **mixed** |  |
| `$camp` | **mixed** |  |





***

### getLastCampaignByCourse



```php
public static getLastCampaignByCourse(mixed $course): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$course` | **mixed** |  |





***

### getMySubmittedCampaign



```php
public getMySubmittedCampaign(): mixed
```












***

### getCampaignByApplicant



```php
public getCampaignByApplicant(mixed $aid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |





***

### getCampaignByFnum



```php
public getCampaignByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getCampaignSubmittedByApplicant



```php
public getCampaignSubmittedByApplicant(mixed $aid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |





***

### setSelectedCampaign



```php
public setSelectedCampaign(mixed $cid, mixed $aid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |
| `$aid` | **mixed** |  |





***

### setResultLetterSent



```php
public setResultLetterSent(mixed $aid, mixed $campaign_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |
| `$campaign_id` | **mixed** |  |





***

### isOtherActiveCampaign



```php
public isOtherActiveCampaign(mixed $aid): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |





***

### getPagination



```php
public getPagination(): \JPagination
```












***

### getTotal



```php
public getTotal(): false|int
```












***

### getCampaignsXLS



```php
public getCampaignsXLS(): array|mixed
```












***

### addCampaignsForProgrammes

Method to create a new compaign for all active programmes.

```php
public addCampaignsForProgrammes(array $data, array $programmes): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | The data to use as campaign definition. |
| `$programmes` | **array** | The list of programmes who need a new campaign. |


**Return Value:**

Does it work.




***

### getLatestCampaign

Gets the most recent campaign programme code.

```php
public getLatestCampaign(): string
```









**Return Value:**

The most recent campaign programme in the DB.




***

### getCCITU

Gets all elements in teaching unity table

```php
public getCCITU(): array
```












***

### getTeachingUnity



```php
public getTeachingUnity(null $id = null): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **null** |  |





***

### getLimit

Get campaign limit params

```php
public getLimit(mixed $id): object|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### isLimitObtained

Check if campaign's limit is obtained

```php
public isLimitObtained(mixed $id): object|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getAssociatedCampaigns

Get associated campaigns

```php
public getAssociatedCampaigns(mixed $filter = &#039;&#039;, mixed $sort = &#039;DESC&#039;, mixed $recherche = &#039;&#039;, mixed $lim = 25, mixed $page, mixed $program = &#039;all&#039;, mixed $session = &#039;all&#039;): array|mixed|\stdClass
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filter` | **mixed** |  |
| `$sort` | **mixed** |  |
| `$recherche` | **mixed** |  |
| `$lim` | **mixed** |  |
| `$page` | **mixed** |  |
| `$program` | **mixed** |  |
| `$session` | **mixed** |  |





***

### getCampaignsByProgramId

Get campaigns by program id

```php
public getCampaignsByProgramId(mixed $program): array|mixed|\stdClass
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$program` | **mixed** |  |





***

### deleteCampaign

Delete a campaign

```php
public deleteCampaign(mixed $data, bool $force_delete = false): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |
| `$force_delete` | **bool** | - if true, delete campaign even if it has files, and delete files too<br />Force delete is only available for super admin users because it can be dangerous |





***

### unpublishCampaign



```php
public unpublishCampaign(mixed $data): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |





***

### publishCampaign



```php
public publishCampaign(mixed $data): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |





***

### duplicateCampaign



```php
public duplicateCampaign(mixed $id): false|mixed|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getYears



```php
public getYears(): array|mixed
```












***

### createCampaign



```php
public createCampaign(mixed $data): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |


**Return Value:**

campaign_id, 0 if failed




***

### updateCampaign



```php
public updateCampaign(mixed $data, mixed $cid): bool|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |
| `$cid` | **mixed** |  |





***

### createYear



```php
public createYear(mixed $data, mixed $profile = null): bool|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |
| `$profile` | **mixed** |  |





***

### getCampaignDetailsById



```php
public getCampaignDetailsById(mixed $id): false|\stdClass
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getCreatedCampaign



```php
public getCreatedCampaign(): false|mixed|null
```












***

### updateProfile



```php
public updateProfile(mixed $profile, mixed $campaign): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile` | **mixed** |  |
| `$campaign` | **mixed** |  |





***

### getCampaignsToAffect

Get campaigns without applicant files

```php
public getCampaignsToAffect(): array|mixed
```












***

### getCampaignsToAffectByTerm



```php
public getCampaignsToAffectByTerm(mixed $term): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$term` | **mixed** |  |





***

### createDocument



```php
public createDocument(mixed $document, mixed $types, mixed $pid): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$document` | **mixed** |  |
| `$types` | **mixed** |  |
| `$pid` | **mixed** |  |





***

### updateDocument



```php
public updateDocument(mixed $document, mixed $types, mixed $did, mixed $pid, mixed $params = []): bool|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$document` | **mixed** |  |
| `$types` | **mixed** |  |
| `$did` | **mixed** |  |
| `$pid` | **mixed** |  |
| `$params` | **mixed** |  |





***

### updatedDocumentMandatory



```php
public updatedDocumentMandatory(mixed $did, mixed $pid, mixed $mandatory = 1): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |
| `$pid` | **mixed** |  |
| `$mandatory` | **mixed** |  |





***

### getCampaignCategory



```php
public getCampaignCategory(mixed $cid): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |





***

### getCampaignDropfilesDocuments



```php
public getCampaignDropfilesDocuments(mixed $campaign_cat): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_cat` | **mixed** |  |





***

### getDropfileDocument



```php
public getDropfileDocument(mixed $did): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |





***

### deleteDocumentDropfile



```php
public deleteDocumentDropfile(mixed $did): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |





***

### editDocumentDropfile



```php
public editDocumentDropfile(mixed $did, mixed $name): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |
| `$name` | **mixed** |  |





***

### updateOrderDropfileDocuments



```php
public updateOrderDropfileDocuments(mixed $documents): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$documents` | **mixed** |  |





***

### getFormDocuments



```php
public getFormDocuments(mixed $pid): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** |  |





***

### editDocumentForm



```php
public editDocumentForm(mixed $did, mixed $name, mixed $pid): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |
| `$name` | **mixed** |  |
| `$pid` | **mixed** |  |





***

### deleteDocumentForm



```php
public deleteDocumentForm(mixed $did, mixed $pid): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |
| `$pid` | **mixed** |  |





***

### getCurrentCampaignWorkflow



```php
public getCurrentCampaignWorkflow(mixed $fnum): false|object
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |


**Return Value:**

False if error, object containing emundus_campaign_workflow id, start date and end_date if success




***

### getAllCampaignWorkflows



```php
public getAllCampaignWorkflows(mixed $campaign_id): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **mixed** | int |





***

### pinCampaign



```php
public pinCampaign(mixed $cid): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |





***

### unpinCampaign



```php
public unpinCampaign(mixed $campaign_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **mixed** |  |





***

### createWorkflow

Create a workflow

```php
public createWorkflow(mixed $profile, mixed $entry_status, mixed $output_status, mixed $start_date = null, mixed $params = []): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile` | **mixed** | int |
| `$entry_status` | **mixed** | array |
| `$output_status` | **mixed** | int |
| `$start_date` | **mixed** | date |
| `$params` | **mixed** | array of optional parameters (campaigns, programs, end_date) |





***

### canCreateWorkflow



```php
public canCreateWorkflow(mixed $profile, mixed $entry_status, mixed $params): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile` | **mixed** |  |
| `$entry_status` | **mixed** |  |
| `$params` | **mixed** |  |





***

### deleteWorkflows



```php
public deleteWorkflows(mixed $ids = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |





***

### getWorkflows



```php
public getWorkflows(mixed $ids = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |





***

### findWorkflowIncoherences



```php
public findWorkflowIncoherences(): array
```












***

### getCampaignMoreFormUrl



```php
public getCampaignMoreFormUrl(mixed $campaign_id): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **mixed** | int |





***

### getAllItemsAlias



```php
public getAllItemsAlias(mixed $cid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |





***

### createCampaignAlias



```php
public createCampaignAlias(mixed $cid, mixed $alias, mixed $label): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |
| `$alias` | **mixed** |  |
| `$label` | **mixed** |  |





***

### getCampaignDetailsMenu



```php
public getCampaignDetailsMenu(mixed $cid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |





***


***
> Automatically generated on 2024-08-19
