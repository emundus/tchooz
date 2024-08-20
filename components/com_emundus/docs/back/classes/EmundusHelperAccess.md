***

# EmundusHelperAccess

Content Component Query Helper



* Full name: `\EmundusHelperAccess`




## Methods


### isAllowed



```php
public static isAllowed(mixed $usertype, mixed $allowed): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$usertype` | **mixed** |  |
| `$allowed` | **mixed** |  |





***

### isAllowedAccessLevel



```php
public static isAllowedAccessLevel(mixed $user_id, mixed $current_menu_access): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$current_menu_access` | **mixed** |  |





***

### asAdministratorAccessLevel



```php
public static asAdministratorAccessLevel(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### asCoordinatorAccessLevel



```php
public static asCoordinatorAccessLevel(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### asManagerAccessLevel



```php
public static asManagerAccessLevel(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### asPartnerAccessLevel



```php
public static asPartnerAccessLevel(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### asEvaluatorAccessLevel



```php
public static asEvaluatorAccessLevel(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### asApplicantAccessLevel



```php
public static asApplicantAccessLevel(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### asPublicAccessLevel



```php
public static asPublicAccessLevel(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### check_group



```php
public static check_group(mixed $user_id, mixed $group, mixed $inherited): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$group` | **mixed** |  |
| `$inherited` | **mixed** |  |





***

### isAdministrator



```php
public static isAdministrator(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### isCoordinator



```php
public static isCoordinator(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### isPartner



```php
public static isPartner(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### isExpert



```php
public static isExpert(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### isEvaluator



```php
public static isEvaluator(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### isApplicant



```php
public static isApplicant(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### isPublic



```php
public static isPublic(mixed $user_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getProfileAccess

Get the eMundus groups for a user.

```php
public static getProfileAccess(int $user): array
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **int** | The user id. |


**Return Value:**

The array of groups for user.




***

### asAccessAction

Get action access right.

```php
public static asAccessAction(int $action_id, string $crud, null $user_id = null, null $fnum = null): bool
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$action_id` | **int** | Id of the action. |
| `$crud` | **string** | create/read/update/delete. |
| `$user_id` | **null** | The user id. |
| `$fnum` | **null** | File number of application |


**Return Value:**

Has access or not




***

### canAccessGroup



```php
public static canAccessGroup(mixed $gids, mixed $action_id, mixed $crud, null $fnum = null): bool
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gids` | **mixed** |  |
| `$action_id` | **mixed** |  |
| `$crud` | **mixed** |  |
| `$fnum` | **null** |  |





***

### getUserFabrikGroups



```php
public static getUserFabrikGroups(mixed $user_id): array|bool
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getUserAllowedAttachmentIDs



```php
public static getUserAllowedAttachmentIDs(mixed $user_id): array|bool
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### isDataAnonymized



```php
public static isDataAnonymized(mixed $user_id): bool
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### isUserAllowedToAccessFnum



```php
public static isUserAllowedToAccessFnum(mixed $user_id, mixed $fnum): bool
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### getCrypt



```php
public static getCrypt(): \JCrypt
```



* This method is **static**.








***

### buildFormUrl



```php
public static buildFormUrl(mixed $link, mixed $fnum): string
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$link` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### getRowIdByFnum



```php
public static getRowIdByFnum(mixed $db_table_name, mixed $fnum): int
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db_table_name` | **mixed** |  |
| `$fnum` | **mixed** |  |





***


***
> Automatically generated on 2024-08-20
