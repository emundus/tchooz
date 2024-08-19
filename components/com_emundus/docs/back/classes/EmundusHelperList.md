***

# EmundusHelperList

Content Component Query Helper



* Full name: `\EmundusHelperList`




## Methods


### aggregation



```php
public aggregation(mixed $array1, mixed $array2, mixed $array3 = array(), mixed $array4 = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$array1` | **mixed** |  |
| `$array2` | **mixed** |  |
| `$array3` | **mixed** |  |
| `$array4` | **mixed** |  |





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

### getEvaluation



```php
public getEvaluation(mixed $user_id, mixed $campaign_id, mixed $eval_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$campaign_id` | **mixed** |  |
| `$eval_id` | **mixed** |  |





***

### isEvaluatedBy



```php
public isEvaluatedBy(mixed $user_id, mixed $campaign_id, mixed $eval_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$campaign_id` | **mixed** |  |
| `$eval_id` | **mixed** |  |





***

### isAffectedToMe



```php
public isAffectedToMe(mixed $user_id, mixed $campaign_id, mixed $user_eval): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$campaign_id` | **mixed** |  |
| `$user_eval` | **mixed** |  |





***

### assessorsList



```php
public assessorsList(mixed $user_id, mixed $campaign_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$campaign_id` | **mixed** |  |





***

### getComment



```php
public getComment(mixed $user_id, mixed $eval_id, mixed $campaign_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$eval_id` | **mixed** |  |
| `$campaign_id` | **mixed** |  |





***

### getFilesRequest



```php
public getFilesRequest(mixed $user_id, mixed $campaign_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$campaign_id` | **mixed** |  |





***

### getUsersGroups



```php
public getUsersGroups(): mixed
```












***

### getUserInfo



```php
public getUserInfo(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getAvatar



```php
public getAvatar(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getProfile



```php
public getProfile(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getProfileDetails



```php
public getProfileDetails(mixed $pid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** |  |





***

### getUploadList



```php
public getUploadList(mixed $user): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |





***

### getFormsList



```php
public getFormsList(mixed $user_id, mixed $fnum = &#039;0&#039;, mixed $formids = null, mixed $profile_id = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$formids` | **mixed** |  |
| `$profile_id` | **mixed** |  |





***

### getFormsListByProfileID



```php
public static getFormsListByProfileID(mixed $profile_id, mixed $checklevel = true): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |
| `$checklevel` | **mixed** |  |





***

### getApplicants



```php
public getApplicants(mixed $submitted, mixed $year): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$submitted` | **mixed** |  |
| `$year` | **mixed** |  |





***

### getCampaignsByApplicantID



```php
public getCampaignsByApplicantID(mixed $user, mixed $submitted, mixed $year): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |
| `$submitted` | **mixed** |  |
| `$year` | **mixed** |  |





***

### createApplicantsCampaignsBlock



```php
public createApplicantsCampaignsBlock(mixed $users, mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$params` | **mixed** |  |





***

### createActionsBlock



```php
public createActionsBlock(mixed $users, mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$params` | **mixed** |  |





***

### createValidateBlock



```php
public createValidateBlock(mixed $users, mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$params` | **mixed** |  |





***

### createSelectionBlock



```php
public createSelectionBlock(mixed $users): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |





***

### createEvaluationBlock



```php
public createEvaluationBlock(mixed $users, mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$params` | **mixed** |  |





***

### createEvaluatorBlock



```php
public createEvaluatorBlock(mixed $users, mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$params` | **mixed** |  |





***

### createFilesRequestBlock



```php
public createFilesRequestBlock(mixed $users): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |





***

### createCommentBlock



```php
public createCommentBlock(mixed $users): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |





***

### array_uniquecolumn



```php
public array_uniquecolumn(mixed $arr, mixed $key): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$arr` | **mixed** |  |
| `$key` | **mixed** |  |





***

### getEngaged



```php
public getEngaged(mixed $users): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |





***

### getProfiles



```php
public getProfiles(): mixed
```












***

### createProfileBlock



```php
public createProfileBlock(mixed $users, mixed $key): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$key` | **mixed** |  |





***

### getApplicationComments



```php
public getApplicationComments(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### createApplicationCommentBlock



```php
public createApplicationCommentBlock(mixed $users, mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$params` | **mixed** |  |





***

### createShowCommentBlock



```php
public createShowCommentBlock(): mixed
```












***

### createBatchBlock



```php
public createBatchBlock(): mixed
```












***

### createApplicationStatutblock



```php
public createApplicationStatutblock(mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### getElementsDetailsByID



```php
public static getElementsDetailsByID(mixed $elements): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements` | **mixed** |  |





***

### getElementsDetails



```php
public static getElementsDetails(mixed $elements): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements` | **mixed** |  |





***

### getElementsDetailsByName



```php
public static getElementsDetailsByName(mixed $elements): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements` | **mixed** |  |





***

### getElementsDetailsByFullName



```php
public getElementsDetailsByFullName(mixed $fullname): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fullname` | **mixed** |  |





***

### getBoxValue



```php
public static getBoxValue(mixed $details, mixed $default, mixed $name): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$details` | **mixed** |  |
| `$default` | **mixed** |  |
| `$name` | **mixed** |  |





***

### createHtmlList



```php
public static createHtmlList(mixed $tab): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tab` | **mixed** |  |





***


***
> Automatically generated on 2024-08-19
