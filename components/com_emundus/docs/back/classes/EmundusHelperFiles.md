***

# EmundusHelperFiles

eMundus Component Query Helper



* Full name: `\EmundusHelperFiles`




## Methods


### clear



```php
public static clear(): mixed
```



* This method is **static**.








***

### clearfilter



```php
public static clearfilter(): mixed
```



* This method is **static**.








***

### setMenuFilter



```php
public setMenuFilter(): mixed
```












***

### resetFilter



```php
public static resetFilter(): mixed
```



* This method is **static**.








***

### insertValuesInQueryResult



```php
public static insertValuesInQueryResult(mixed $results, mixed $options): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$results` | **mixed** |  |
| `$options` | **mixed** |  |





***

### getCurrentCampaign



```php
public static getCurrentCampaign(): mixed
```



* This method is **static**.








***

### getCurrentCampaignsID



```php
public static getCurrentCampaignsID(): mixed
```



* This method is **static**.








***

### getCampaigns



```php
public getCampaigns(): mixed
```












***

### getProgramCampaigns



```php
public getProgramCampaigns(mixed $code): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getProgrammes



```php
public getProgrammes(mixed $code = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |





***

### getStatus



```php
public static getStatus(): mixed
```



* This method is **static**.








***

### getCampaign



```php
public static getCampaign(): mixed
```



* This method is **static**.








***

### getCampaignByID



```php
public static getCampaignByID(mixed $id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getApplicants



```php
public static getApplicants(): mixed
```



* This method is **static**.








***

### getProfiles



```php
public getProfiles(): mixed
```












***

### getEvaluators



```php
public getEvaluators(): mixed
```












***

### getGroupsEval



```php
public getGroupsEval(): mixed
```












***

### getGroups



```php
public getGroups(): mixed
```












***

### getSchoolyears



```php
public getSchoolyears(): mixed
```












***

### getFinal_grade



```php
public getFinal_grade(): mixed
```












***

### getMissing_doc



```php
public getMissing_doc(): mixed
```












***

### getAttachmentsTypesByProfileID



```php
public getAttachmentsTypesByProfileID(mixed $pid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** |  |





***

### getEvaluation_doc



```php
public getEvaluation_doc(mixed $status): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$status` | **mixed** |  |





***

### setEvaluationList



```php
public setEvaluationList(mixed $status): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$status` | **mixed** |  |





***

### getElements



```php
public static getElements(array $code = array(), array $camps = array(), array $fabrik_elements = array(), int $profile = null): array
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **array** |  |
| `$camps` | **array** |  |
| `$fabrik_elements` | **array** |  |
| `$profile` | **int** | --&gt; to get all form elems of a profile |





***

### getElementById

Get Fabrik element by ID

```php
public getElementById(mixed $element_id): object
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element_id` | **mixed** |  |


**Return Value:**

Fabrik element




***

### getPhotos



```php
public getPhotos(mixed $fnum = null): array|false|string|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getElementsByGroups

Get list of elements declared in a list of Fabrik groups

```php
public getElementsByGroups(mixed $groups, mixed $show_in_list_summary = 1, mixed $hidden): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groups` | **mixed** |  |
| `$show_in_list_summary` | **mixed** |  |
| `$hidden` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in evaluation form




***

### getElementsOther



```php
public getElementsOther(mixed $tables): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tables` | **mixed** |  |





***

### getElementsValuesOther



```php
public getElementsValuesOther(mixed $element_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element_id` | **mixed** |  |





***

### getElementsName



```php
public static getElementsName(mixed $elements_id): array|false
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements_id` | **mixed** | string of elements id separated by comma |





***

### getFabrikElementValue



```php
public getFabrikElementValue(mixed $fnum, mixed $element_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$element_id` | **mixed** |  |





***

### getFabrikElementValues



```php
public getFabrikElementValues(mixed $fnum, mixed $element_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$element_ids` | **mixed** |  |





***

### getElementsDetailsByID



```php
public getElementsDetailsByID(mixed $elements): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements` | **mixed** |  |





***

### buildOptions



```php
public buildOptions(mixed $element_name, mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element_name` | **mixed** |  |
| `$params` | **mixed** |  |





***

### setWhere



```php
public static setWhere(mixed $search, mixed $search_values, mixed& $query): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$search` | **mixed** |  |
| `$search_values` | **mixed** |  |
| `$query` | **mixed** |  |





***

### setSearchBox



```php
public setSearchBox(mixed $selected, mixed $search_value, mixed $elements_values, mixed $i): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$selected` | **mixed** |  |
| `$search_value` | **mixed** |  |
| `$elements_values` | **mixed** |  |
| `$i` | **mixed** |  |





***

### createFilterBlock



```php
public createFilterBlock(mixed $params, mixed $types, mixed $tables): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |
| `$types` | **mixed** |  |
| `$tables` | **mixed** |  |





***

### getEmundusFilters



```php
public static getEmundusFilters(mixed $id = null): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### createTagsList



```php
public static createTagsList(mixed $tags): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tags` | **mixed** |  |





***

### createFormProgressList



```php
public createFormProgressList(mixed $formsprogress): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$formsprogress` | **mixed** |  |





***

### createAttachmentProgressList



```php
public createAttachmentProgressList(mixed $attachmentsprogress): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attachmentsprogress` | **mixed** |  |





***

### createUnreadMessageList



```php
public createUnreadMessageList(mixed $unread_messages): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$unread_messages` | **mixed** |  |





***

### createHTMLList

Create a list of HTML text using the tag system.

```php
public createHTMLList(mixed $html, mixed $fnums): array
```

This function replaces the tags found in an HTML block with information from the fnums.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$html` | **mixed** | String The block of text containing the tags to be replaced. |
| `$fnums` | **mixed** | array The list of fnums to use for the tags. |





***

### createEvaluatorList



```php
public static createEvaluatorList(mixed $join, mixed $model): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$join` | **mixed** |  |
| `$model` | **mixed** |  |





***

### getMenuList



```php
public static getMenuList(mixed $params, mixed $fnum = null): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### getUserGroups



```php
public getUserGroups(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getEvaluation



```php
public static getEvaluation(mixed $format = &#039;html&#039;, mixed $fnums = []): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$format` | **mixed** |  |
| `$fnums` | **mixed** |  |





***

### getDecision



```php
public static getDecision(mixed $format = &#039;html&#039;, mixed $fnums = []): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$format` | **mixed** |  |
| `$fnums` | **mixed** |  |





***

### getAdmission



```php
public getAdmission(mixed $format = &#039;html&#039;, mixed $fnums = [], mixed $name = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$format` | **mixed** |  |
| `$fnums` | **mixed** |  |
| `$name` | **mixed** |  |





***

### getInterview



```php
public getInterview(mixed $format = &#039;html&#039;, mixed $fnums = []): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$format` | **mixed** |  |
| `$fnums` | **mixed** |  |





***

### createFnum

Function to create a new FNUM

```php
public static createFnum(mixed $campaign_id, mixed $user_id, mixed $redirect = true): string
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **mixed** |  |
| `$user_id` | **mixed** |  |
| `$redirect` | **mixed** |  |


**Return Value:**

FNUM for application.




***

### tableExists

Checks if a table exists in the database.

```php
public tableExists(mixed $table_name): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table_name` | **mixed** |  |


**Return Value:**

True if table found, else false.




***

### saveExcelFilter



```php
public saveExcelFilter(mixed $user_id, mixed $name, mixed $constraints, mixed $time_date, mixed $itemid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$name` | **mixed** |  |
| `$constraints` | **mixed** |  |
| `$time_date` | **mixed** |  |
| `$itemid` | **mixed** |  |





***

### savePdfFilter



```php
public savePdfFilter(mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### deletePdfFilter



```php
public deletePdfFilter(mixed $fid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fid` | **mixed** |  |





***

### getExportExcelFilter

if empty $user_id, then it will return false
if not empty $user_id, then it will return all the filters of the user, empty array if no filters

```php
public getExportExcelFilter(mixed $user_id): array|false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getAllExportPdfFilter



```php
public getAllExportPdfFilter(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getExportPdfFilterById



```php
public getExportPdfFilterById(mixed $model_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model_id` | **mixed** |  |





***

### getFabrikDataByListElements



```php
public getFabrikDataByListElements(mixed $elements): array|false|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements` | **mixed** |  |





***

### getExportExcelFilterById



```php
public getExportExcelFilterById(mixed $fid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fid` | **mixed** |  |





***

### getAllLetters



```php
public getAllLetters(): mixed
```












***

### getExcelLetterById



```php
public getExcelLetterById(mixed $lid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lid` | **mixed** |  |





***

### checkadmission



```php
public checkadmission(): mixed
```












***

### getSelectedElements



```php
public getSelectedElements(mixed $selectedElts): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$selectedElts` | **mixed** |  |





***

### _buildWhere



```php
public _buildWhere(array $tableAlias = array(), mixed $caller = &#039;files&#039;, mixed $caller_params = array()): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tableAlias` | **array** |  |
| `$caller` | **mixed** |  |
| `$caller_params` | **mixed** |  |





***

### _moduleBuildWhere



```php
public _moduleBuildWhere(array $already_joined = array(), string $caller = &#039;files&#039;, array $caller_params = [], mixed $filters_to_exclude = [], mixed $menu_item = null, mixed $user = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$already_joined` | **array** |  |
| `$caller` | **string** |  |
| `$caller_params` | **array** |  |
| `$filters_to_exclude` | **mixed** |  |
| `$menu_item` | **mixed** |  |
| `$user` | **mixed** |  |


**Return Value:**

containing 'q' the where clause and 'join' the join clause




***

### getFabrikElementData



```php
public getFabrikElementData(int $element_id): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element_id` | **int** |  |





***

### getJoinInformations



```php
public getJoinInformations(mixed $element_id, mixed $group_id, mixed $list_id): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element_id` | **mixed** |  |
| `$group_id` | **mixed** |  |
| `$list_id` | **mixed** |  |





***

### findJoinsBetweenTablesRecursively



```php
public findJoinsBetweenTablesRecursively(string $searched_table, string $base_table, int $i): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$searched_table` | **string** |  |
| `$base_table` | **string** |  |
| `$i` | **int** | , the iteration number<br /><br />if the array is empty, it means that the tables are not linked |





***

### writeJoins



```php
public writeJoins(mixed $found_joins, mixed& $already_joined_tables, mixed $create_alias = false): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$found_joins` | **mixed** | array the joins found by findJoinsBetweenTablesRecursively, ordered from the searched table to the base table |
| `$already_joined_tables` | **mixed** | array referenced array |
| `$create_alias` | **mixed** |  |





***

### writeQueryWithOperator



```php
public writeQueryWithOperator(mixed $element, mixed $values, mixed $operator, mixed $type = &#039;select&#039;, mixed $fabrik_element_data = null, mixed $andor = &#039;OR&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$values` | **mixed** |  |
| `$operator` | **mixed** |  |
| `$type` | **mixed** |  |
| `$fabrik_element_data` | **mixed** |  |
| `$andor` | **mixed** |  |





***

### notInQuery



```php
private notInQuery(mixed $element, mixed $values, mixed $fabrik_element_data): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$values` | **mixed** |  |
| `$fabrik_element_data` | **mixed** |  |





***

### setFiltersValuesAvailability



```php
public setFiltersValuesAvailability(mixed $applied_filters, mixed $user_id = null, mixed $menu_item = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$applied_filters` | **mixed** |  |
| `$user_id` | **mixed** |  |
| `$menu_item` | **mixed** |  |





***

### _buildSearch



```php
private _buildSearch(mixed $str_array, array $tableAlias = array(), mixed $caller_aprams = array()): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$str_array` | **mixed** |  |
| `$tableAlias` | **array** |  |
| `$caller_aprams` | **mixed** |  |





***

### getEncryptedTables



```php
public getEncryptedTables(): mixed
```












***

### getApplicantFnums



```php
public getApplicantFnums(int $aid, mixed $submitted = null, mixed $start_date = null, mixed $end_date = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **int** |  |
| `$submitted` | **mixed** |  |
| `$start_date` | **mixed** |  |
| `$end_date` | **mixed** |  |





***

### isTableLinkedToCampaignCandidature



```php
public isTableLinkedToCampaignCandidature(mixed $table_name): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table_name` | **mixed** |  |





***


***
> Automatically generated on 2024-08-19
