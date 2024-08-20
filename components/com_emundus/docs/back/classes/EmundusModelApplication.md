***

# EmundusModelApplication





* Full name: `\EmundusModelApplication`
* Parent class: [`JModelList`](./JModelList.md)



## Properties


### _mainframe



```php
private $_mainframe
```






***

### h_cache



```php
private $h_cache
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

## Methods


### __construct

Constructor

```php
public __construct(): mixed
```













***

### getApplicantInfos



```php
public getApplicantInfos(mixed $aid, mixed $param): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |
| `$param` | **mixed** |  |






***

### getApplicantDetails



```php
public getApplicantDetails(mixed $aid, mixed $ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |
| `$ids` | **mixed** |  |






***

### getUserCampaigns



```php
public getUserCampaigns(mixed $id, mixed $cid = null, mixed $published_only = true): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$cid` | **mixed** |  |
| `$published_only` | **mixed** |  |






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

### getUserAttachments



```php
public getUserAttachments(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getUserAttachmentsByFnum



```php
public getUserAttachmentsByFnum(mixed $fnum, mixed $search = &#039;&#039;, mixed $profile = null, mixed $applicant = false, mixed $user_id = null): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$search` | **mixed** |  |
| `$profile` | **mixed** |  |
| `$applicant` | **mixed** |  |
| `$user_id` | **mixed** |  |






***

### getUsersComments



```php
public getUsersComments(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getComment



```php
public getComment(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getTag



```php
public getTag(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getFileComments



```php
public getFileComments(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getFileOwnComments



```php
public getFileOwnComments(mixed $fnum, mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$user_id` | **mixed** |  |






***

### editComment



```php
public editComment(mixed $id, mixed $title, mixed $text): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$title` | **mixed** |  |
| `$text` | **mixed** |  |






***

### deleteComment



```php
public deleteComment(mixed $id, mixed $fnum = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$fnum` | **mixed** |  |






***

### deleteTag



```php
public deleteTag(mixed $id_tag, mixed $fnum, mixed $user_id = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id_tag` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$user_id` | **mixed** |  |






***

### addComment



```php
public addComment(mixed $row): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$row` | **mixed** |  |






***

### deleteData



```php
public deleteData(mixed $id, mixed $table): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$table` | **mixed** |  |






***

### deleteAttachment



```php
public deleteAttachment(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### uploadAttachment



```php
public uploadAttachment(mixed $data): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |






***

### getAttachmentByID



```php
public getAttachmentByID(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getAttachmentByLbl



```php
public getAttachmentByLbl(mixed $label): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |






***

### getUploadByID



```php
public getUploadByID(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### isFormFilled

Check if forms of a profile are filled, even partially

```php
public isFormFilled(mixed $profile_id, mixed $fnum): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |
| `$fnum` | **mixed** |  |






***

### getFormsProgress



```php
public getFormsProgress(string $fnum = &quot;0&quot;, mixed $euser = null): array|bool|false|float
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **string** |  |
| `$euser` | **mixed** |  |






***

### getFormsProgressWithProfile



```php
public getFormsProgressWithProfile(mixed $fnum, mixed $profile_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$profile_id` | **mixed** |  |






***

### updateFormProgressByFnum



```php
public updateFormProgressByFnum(mixed $result, mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | **mixed** |  |
| `$fnum` | **mixed** |  |






***

### getFilesProgress



```php
public getFilesProgress(mixed $fnum = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getAttachmentsProgress



```php
public getAttachmentsProgress(mixed $fnums = null, mixed $euser = null): array|bool|false|float
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$euser` | **mixed** |  |






***

### getAttachmentsProgressWithProfile



```php
public getAttachmentsProgressWithProfile(mixed $fnum, mixed $profile_id): array|bool|false|float
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$profile_id` | **mixed** |  |






***

### updateAttachmentProgressByFnum



```php
public updateAttachmentProgressByFnum(mixed $result, mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | **mixed** |  |
| `$fnum` | **mixed** |  |






***

### checkFabrikValidations



```php
public checkFabrikValidations(mixed $fnum, mixed $redirect = false, mixed $itemId = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$redirect` | **mixed** |  |
| `$itemId` | **mixed** |  |






***

### getLogged



```php
public getLogged(mixed $aid, mixed $user = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |
| `$user` | **mixed** |  |






***

### getFormByFabrikFormID



```php
public getFormByFabrikFormID(mixed $formID, mixed $aid, int $fnum): string|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$formID` | **mixed** |  |
| `$aid` | **mixed** |  |
| `$fnum` | **int** |  |






***

### getForms



```php
public getForms(mixed $aid, mixed $fnum, mixed $pid = 9): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$pid` | **mixed** |  |






***

### getFormsPDF



```php
public getFormsPDF(mixed $aid, mixed $fnum, mixed $fids = null, mixed $gids, mixed $profile_id = null, mixed $eids = null, mixed $attachments = true): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$fids` | **mixed** |  |
| `$gids` | **mixed** |  |
| `$profile_id` | **mixed** |  |
| `$eids` | **mixed** |  |
| `$attachments` | **mixed** |  |






***

### getFormsPDFElts



```php
public getFormsPDFElts(mixed $aid, mixed $elts, mixed $options, mixed $checklevel = true): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |
| `$elts` | **mixed** |  |
| `$options` | **mixed** |  |
| `$checklevel` | **mixed** |  |






***

### getEmail



```php
public getEmail(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |






***

### getApplicationMenu



```php
public getApplicationMenu(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |






***

### getProgramSynthesis



```php
public getProgramSynthesis(mixed $cid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |






***

### getAttachments



```php
public getAttachments(mixed $ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |






***

### getAttachmentsByFnum



```php
public getAttachmentsByFnum(mixed $fnum, mixed $ids = null, mixed $attachment_id = null, mixed $profile = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$ids` | **mixed** |  |
| `$attachment_id` | **mixed** |  |
| `$profile` | **mixed** |  |






***

### getAccessFnum



```php
public getAccessFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getActions



```php
public getActions(): mixed
```













***

### checkGroupAssoc



```php
public checkGroupAssoc(mixed $fnum, mixed $gid, mixed $aid = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$gid` | **mixed** |  |
| `$aid` | **mixed** |  |






***

### updateGroupAccess



```php
public updateGroupAccess(mixed $fnum, mixed $gid, mixed $actionId, mixed $crud, mixed $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$gid` | **mixed** |  |
| `$actionId` | **mixed** |  |
| `$crud` | **mixed** |  |
| `$value` | **mixed** |  |






***

### _addGroupAssoc



```php
private _addGroupAssoc(mixed $fnum, mixed $crud, mixed $aid, mixed $gid, mixed $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$crud` | **mixed** |  |
| `$aid` | **mixed** |  |
| `$gid` | **mixed** |  |
| `$value` | **mixed** |  |






***

### checkUserAssoc



```php
public checkUserAssoc(mixed $fnum, mixed $uid, mixed $aid = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$uid` | **mixed** |  |
| `$aid` | **mixed** |  |






***

### _addUserAssoc



```php
private _addUserAssoc(mixed $fnum, mixed $crud, mixed $aid, mixed $uid, mixed $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$crud` | **mixed** |  |
| `$aid` | **mixed** |  |
| `$uid` | **mixed** |  |
| `$value` | **mixed** |  |






***

### updateUserAccess



```php
public updateUserAccess(mixed $fnum, mixed $uid, mixed $actionId, mixed $crud, mixed $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$uid` | **mixed** |  |
| `$actionId` | **mixed** |  |
| `$crud` | **mixed** |  |
| `$value` | **mixed** |  |






***

### deleteGroupAccess



```php
public deleteGroupAccess(mixed $fnum, mixed $gid, mixed $current_user = null): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** | string |
| `$gid` | **mixed** | int |
| `$current_user` | **mixed** | int If null, the current user will be used |






***

### deleteUserAccess



```php
public deleteUserAccess(mixed $fnum, mixed $uid, mixed $current_user = null): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** | string |
| `$uid` | **mixed** | int |
| `$current_user` | **mixed** | int if null, the current user will be used |






***

### getApplications



```php
public getApplications(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |






***

### getApplication



```php
public getApplication(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getHikashopOrder

Return the order for current fnum. If an order with confirmed status is found for fnum campaign period, then return the order
If $sent is sent to true, the function will search for orders with a status of 'created' and offline paiement methode

```php
public getHikashopOrder(mixed $fnumInfos, bool $cancelled = false, mixed $confirmed = true): bool|object
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnumInfos` | **mixed** | $sent |
| `$cancelled` | **bool** |  |
| `$confirmed` | **mixed** |  |






***

### getHikashopCartOrder



```php
public getHikashopCartOrder(mixed $fnumInfos, mixed $cancelled = false, mixed $confirmed = true): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnumInfos` | **mixed** |  |
| `$cancelled` | **mixed** |  |
| `$confirmed` | **mixed** |  |






***

### getHikashopCart



```php
public getHikashopCart(mixed $fnumInfos): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnumInfos` | **mixed** |  |






***

### getHikashopCheckoutUrl

Return the checkout URL order for current fnum.

```php
public getHikashopCheckoutUrl(mixed $pid): bool|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** | string&amp;#124;int   the applicant&#039;s profile_id |






***

### getHikashopCartUrl

Return the checkout URL order for current fnum.

```php
public getHikashopCartUrl(mixed $pid): bool|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** | string&amp;#124;int   the applicant&#039;s profile_id |






***

### moveApplication

Move an application file from one programme to another

```php
public moveApplication(mixed $fnum_from, mixed $fnum_to, mixed $campaign, null $status = null, mixed $params = array()): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum_from` | **mixed** | String the fnum of the source |
| `$fnum_to` | **mixed** | String the fnum of the moved application |
| `$campaign` | **mixed** | String the programme id to move the file to |
| `$status` | **null** |  |
| `$params` | **mixed** |  |






***

### copyApplication

Duplicate an application file (form data)

```php
public copyApplication(mixed $fnum_from, mixed $fnum_to, mixed $pid = null, mixed $copy_attachment, mixed $campaign_id = null, mixed $copy_tag, mixed $move_hikashop_command, mixed $delete_from_file, mixed $params = array(), mixed $copyUsersAssoc, mixed $copyGroupsAssoc): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum_from` | **mixed** | String the fnum of the source |
| `$fnum_to` | **mixed** | String the fnum of the duplicated application |
| `$pid` | **mixed** | Int the profile_id to get list of forms |
| `$copy_attachment` | **mixed** |  |
| `$campaign_id` | **mixed** |  |
| `$copy_tag` | **mixed** |  |
| `$move_hikashop_command` | **mixed** |  |
| `$delete_from_file` | **mixed** |  |
| `$params` | **mixed** |  |
| `$copyUsersAssoc` | **mixed** |  |
| `$copyGroupsAssoc` | **mixed** |  |






***

### copyDocuments

Duplicate all documents (files)

```php
public copyDocuments(mixed $fnum_from, mixed $fnum_to, mixed $pid = null, mixed $can_delete = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum_from` | **mixed** | String the fnum of the source |
| `$fnum_to` | **mixed** | String the fnum of the duplicated application |
| `$pid` | **mixed** | Int the profile_id to get list of forms |
| `$can_delete` | **mixed** |  |






***

### copyUsersAssoc



```php
public copyUsersAssoc(mixed $fnum_from, mixed $fnum_to): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum_from` | **mixed** |  |
| `$fnum_to` | **mixed** |  |






***

### copyGroupsAssoc



```php
public copyGroupsAssoc(mixed $fnum_from, mixed $fnum_to): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum_from` | **mixed** |  |
| `$fnum_to` | **mixed** |  |






***

### sendApplication

Duplicate all documents (files)

```php
public sendApplication(mixed $fnum, mixed $applicant, array $param = array(), int $status = 1): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** | String     the fnum of application file |
| `$applicant` | **mixed** | Object     the applicant user ID |
| `$param` | **array** |  |
| `$status` | **int** |  |






***

### allowEmbed

Check if iframe can be used

```php
public allowEmbed(mixed $url): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$url` | **mixed** | String url to check |






***

### getFirstPage

Gets the first page of the application form. Used for opening a file.

```php
public getFirstPage(string $redirect = &#039;index.php&#039;, null $fnums = null): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$redirect` | **string** |  |
| `$fnums` | **null** |  |


**Return Value:**

The URL to the form.





***

### attachment_validation



```php
public attachment_validation(mixed $attachment_id, mixed $state): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attachment_id` | **mixed** |  |
| `$state` | **mixed** |  |






***

### getConfirmUrl

Gets the URL of the final form in the application in order to submit.

```php
public getConfirmUrl(mixed $fnums = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |






***

### searchFilesByKeywords



```php
public searchFilesByKeywords(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### checkEmptyRepeatGroups



```php
public checkEmptyRepeatGroups(mixed $elements, mixed $table, mixed $parent_table, mixed $fnum): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements` | **mixed** |  |
| `$table` | **mixed** |  |
| `$parent_table` | **mixed** |  |
| `$fnum` | **mixed** |  |






***

### checkEmptyGroups



```php
public checkEmptyGroups(mixed $elements, mixed $parent_table, mixed $fnum): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements` | **mixed** |  |
| `$parent_table` | **mixed** |  |
| `$fnum` | **mixed** |  |






***

### getCountUploadedFile



```php
public getCountUploadedFile(mixed $fnum, mixed $user_id, mixed $profile = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$user_id` | **mixed** |  |
| `$profile` | **mixed** |  |






***

### getListUploadedFile



```php
public getListUploadedFile(mixed $fnum, mixed $user_id, mixed $profile = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$user_id` | **mixed** |  |
| `$profile` | **mixed** |  |






***

### updateAttachment

Update emundus upload data in database, and even the file content

```php
public updateAttachment(mixed $data): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |


**Return Value:**

containing status of update and file content update





***

### getAttachmentPreview

Generate preview based on file types

```php
public getAttachmentPreview(mixed $user, mixed $fileName): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |
| `$fileName` | **mixed** |  |


**Return Value:**

preview html tags





***

### convertPowerPointToHTML



```php
private convertPowerPointToHTML(mixed $filePath): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filePath` | **mixed** |  |


**Return Value:**

(html content)





***

### getValuesByElementAndFnum



```php
public getValuesByElementAndFnum(mixed $fnum, mixed $eid, mixed $fid, mixed $raw = 1, mixed $wheres = [], mixed $uid = null, mixed $format = true, mixed $repeate_sperator = &quot;,&quot;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$eid` | **mixed** |  |
| `$fid` | **mixed** |  |
| `$raw` | **mixed** |  |
| `$wheres` | **mixed** |  |
| `$uid` | **mixed** |  |
| `$format` | **mixed** |  |
| `$repeate_sperator` | **mixed** |  |






***

### formatElementValue



```php
public formatElementValue(mixed $element, mixed $value, mixed $table, mixed $applicant_id): mixed
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** | farbik element object |
| `$value` | **mixed** | value of the element |
| `$table` | **mixed** | table name |
| `$applicant_id` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)




***

### invertFnumsOrderByColumn



```php
public invertFnumsOrderByColumn(mixed $fnum_from, mixed $target_fnum, mixed $order_column = &#039;ordering&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum_from` | **mixed** |  |
| `$target_fnum` | **mixed** |  |
| `$order_column` | **mixed** |  |






***

### getSelectFromDBJoinElementParams



```php
private getSelectFromDBJoinElementParams(mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |






***

### createTab



```php
public createTab(mixed $name, mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **mixed** |  |
| `$user_id` | **mixed** |  |






***

### getTabs



```php
public getTabs(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |






***

### updateTabs



```php
public updateTabs(mixed $tabs, mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tabs` | **mixed** |  |
| `$user_id` | **mixed** |  |






***

### deleteTab



```php
public deleteTab(int $tab_id, int $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tab_id` | **int** |  |
| `$user_id` | **int** |  |






***

### moveToTab



```php
public moveToTab(mixed $fnum, mixed $tab): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$tab` | **mixed** |  |






***

### copyFile



```php
public copyFile(mixed $fnum, mixed $fnum_to): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$fnum_to` | **mixed** |  |






***

### renameFile



```php
public renameFile(mixed $fnum, mixed $new_name): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$new_name` | **mixed** |  |






***

### getCampaignsAvailableForCopy



```php
public getCampaignsAvailableForCopy(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### isTabOwnedByUser



```php
public isTabOwnedByUser(mixed $tab_id, mixed $user_id, mixed $fnum = &#039;&#039;): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tab_id` | **mixed** |  |
| `$user_id` | **mixed** | - if empty, check for fnum |
| `$fnum` | **mixed** | - if empty, check for user_id |






***

### applicantCustomAction



```php
public applicantCustomAction(mixed $action, mixed $fnum, mixed $module_id, mixed $redirect = false, mixed $user_id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$action` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$module_id` | **mixed** | if not specified, will use the first published module |
| `$redirect` | **mixed** |  |
| `$user_id` | **mixed** |  |


**Return Value:**

true if the action was done successfully





***


***
> Last updated on 20/08/2024
