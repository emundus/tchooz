***

# EmundusHelperFilters

Content Component Query Helper



* Full name: `\EmundusHelperFilters`




## Methods


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

### getProgrammes



```php
public getProgrammes(): mixed
```












***

### getCampaign



```php
public getCampaign(): mixed
```












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
public getApplicants(): mixed
```












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
public static getGroups(): mixed
```



* This method is **static**.








***

### getSchoolyears



```php
public getSchoolyears(): mixed
```












***

### getFinal_grade



```php
public static getFinal_grade(): mixed
```



* This method is **static**.








***

### getMissing_doc



```php
public getMissing_doc(): mixed
```












***

### getEvaluation_doc



```php
public static getEvaluation_doc(mixed $status): mixed
```



* This method is **static**.




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
public static getElements(): mixed
```



* This method is **static**.








***

### getElementsByGroups

Get list of elements declared in a list of Fabrik groups AND groupe.id IN (551,580,581)

```php
public static getElementsByGroups(mixed $groups, mixed $show_in_list_summary = 1, mixed $hidden): array
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groups` | **mixed** |  |
| `$show_in_list_summary` | **mixed** |  |
| `$hidden` | **mixed** |  |


**Return Value:**

list of Fabrik element ID used in evaluation form




***

### getAllElementsByGroups

Get list of ALL elements declared in a list of Fabrik groups

```php
public static getAllElementsByGroups(mixed $groups, mixed $show_in_list_summary = null, mixed $hidden = null): array
```



* This method is **static**.




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
public static getElementsOther(mixed $tables): mixed
```



* This method is **static**.




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
public getElementsName(mixed $elements_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements_id` | **mixed** |  |





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
public setWhere(mixed $search, mixed $search_values, mixed& $query): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$search` | **mixed** |  |
| `$search_values` | **mixed** |  |
| `$query` | **mixed** |  |





***

### setSearchBox



```php
public setSearchBox(mixed $selected, mixed $search_value, mixed $elements_values): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$selected` | **mixed** |  |
| `$search_value` | **mixed** |  |
| `$elements_values` | **mixed** |  |





***

### getEmundusFilters



```php
public getEmundusFilters(): mixed
```












***


***
> Last updated on 20/08/2024
