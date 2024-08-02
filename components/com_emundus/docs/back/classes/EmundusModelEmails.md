***

# EmundusModelEmails





* Full name: `\EmundusModelEmails`
* Parent class: [`JModelList`](./JModelList.md)



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

## Methods


### __construct

Constructor

```php
public __construct(): mixed
```












***

### getEmail

Get email template by code

```php
public getEmail(mixed $lbl): object|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lbl` | **mixed** | string the email code |


**Return Value:**

The email template object




***

### getEmailById

Get email template by ID

```php
public getEmailById(mixed $id): object
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** | int The email template ID |


**Return Value:**

The email template object




***

### getEmailTrigger

Get email definition to trigger on Status changes

```php
public getEmailTrigger(mixed $step, mixed $code, mixed $to_applicant, mixed $to_current_user = null, mixed $student = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$step` | **mixed** | INT The status of application |
| `$code` | **mixed** | array of programme code |
| `$to_applicant` | **mixed** | int define if trigger concern selected fnum from list or not. Can be 0, 1 |
| `$to_current_user` | **mixed** |  |
| `$student` | **mixed** |  |


**Return Value:**

Emails templates and recipient to trigger




***

### sendEmailTrigger

Send email triggered for Status

```php
public sendEmailTrigger(mixed $step, mixed $code, mixed $to_applicant, mixed $student = null, mixed $to_current_user = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$step` | **mixed** | int The status of application |
| `$code` | **mixed** | array of programme code |
| `$to_applicant` | **mixed** | int define if trigger concern selected fnum or not |
| `$student` | **mixed** | Object Joomla user |
| `$to_current_user` | **mixed** |  |


**Return Value:**

Emails templates and recipient to trigger



**Throws:**

- [`Exception`](./Exception.md)



***

### setBody



```php
public setBody(mixed $user, mixed $str, mixed $passwd = &#039;&#039;): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** | Object      user object |
| `$str` | **mixed** | String      string with tags |
| `$passwd` | **mixed** | String      user password |


**Return Value:**

$strval         String      str with tags replace by value




***

### replace



```php
public replace(mixed $replacement, mixed $str): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$replacement` | **mixed** |  |
| `$str` | **mixed** |  |





***

### stripAccents



```php
public stripAccents(mixed $str): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$str` | **mixed** | string |


**Return Value:**

String with accents stripped




***

### getFabrikElementIDs



```php
public getFabrikElementIDs(mixed $body): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$body` | **mixed** | string |


**Return Value:**

array of application file elements IDs




***

### getFabrikElementValues



```php
public getFabrikElementValues(mixed $fnum, mixed $element_ids): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** | string  application file number |
| `$element_ids` | **mixed** | array   Fabrik element ID |


**Return Value:**

array of application file elements values




***

### setElementValues



```php
public setElementValues(mixed $body, mixed $element_values): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$body` | **mixed** | string  source containing tags like {fabrik_element_id} |
| `$element_values` | **mixed** | array   Array of values index by Fabrik elements IDs |


**Return Value:**

String with values




***

### setConstants



```php
public setConstants(mixed $user_id, mixed $post = null, mixed $passwd = &#039;&#039;, mixed $fnum = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$post` | **mixed** |  |
| `$passwd` | **mixed** |  |
| `$fnum` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### setTags

Define replacement values for tags

```php
public setTags(int $user_id, array $post = null, string $fnum = null, string $passwd = &#039;&#039;, string $content = &#039;&#039;, mixed $base64 = false): array[]
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **int** |  |
| `$post` | **array** | custom tags define from context |
| `$fnum` | **string** | used to get fabrik tags ids from applicant file |
| `$passwd` | **string** | used set password if needed |
| `$content` | **string** | string containing tags to replace, ATTENTION : if empty all tags are computing |
| `$base64` | **mixed** |  |





***

### setTagsWord



```php
public setTagsWord(mixed $user_id, mixed $post = null, mixed $fnum = null, mixed $passwd = &#039;&#039;): array[]
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$post` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$passwd` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### setTagsFabrik



```php
public setTagsFabrik(mixed $str, mixed $fnums = array(), mixed $raw = false): array|string|string[]|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$str` | **mixed** |  |
| `$fnums` | **mixed** |  |
| `$raw` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getCddLabel

Gets the label of a CascadingDropdown element based on the value.

```php
public getCddLabel(mixed $elt, mixed $val): mixed|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elt` | **mixed** | array the cascadingdropdown element. |
| `$val` | **mixed** | string the value of the element to be used for retrieving the label. |





***

### getVariables

Find all variables like ${var} in string.

```php
private getVariables(string $str): string[]
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$str` | **string** |  |





***

### sendMail



```php
public sendMail(mixed $type = null, mixed $fnum = null): array|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **mixed** |  |
| `$fnum` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### sendExpertMail

Used for sending the expert invitation email with the link to the form.

```php
public sendExpertMail(mixed $fnums, mixed $sender_id = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** | array |
| `$sender_id` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### logEmail



```php
public logEmail(mixed $row, mixed $fnum = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$row` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### rand_string



```php
public rand_string(mixed $len, mixed $chars = &#039;abcdefghijklmnopqrstuvwxyz0123456789&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$len` | **mixed** |  |
| `$chars` | **mixed** |  |





***

### get_messages_to_from_user

Gets all emails sent to or from the User id.

```php
public get_messages_to_from_user(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |


**Return Value:**

Array




***

### sendEmailToGroup



```php
public sendEmailToGroup(int $email, array $groups, array $attachments = []): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$email` | **int** |  |
| `$groups` | **array** |  |
| `$attachments` | **array** |  |





***

### sendEmailFromPlatform



```php
public sendEmailFromPlatform(int $user, object $template, array $attachments): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **int** |  |
| `$template` | **object** |  |
| `$attachments` | **array** |  |





***

### getAllEmails



```php
public getAllEmails(mixed $lim, mixed $page, mixed $filter, mixed $sort, mixed $recherche, mixed $category = &#039;&#039;): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lim` | **mixed** |  |
| `$page` | **mixed** |  |
| `$filter` | **mixed** |  |
| `$sort` | **mixed** |  |
| `$recherche` | **mixed** |  |
| `$category` | **mixed** |  |





***

### deleteEmail



```php
public deleteEmail(mixed $ids): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |





***

### unpublishEmail



```php
public unpublishEmail(mixed $data): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |





***

### publishEmail



```php
public publishEmail(mixed $data): false|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |





***

### duplicateEmail



```php
public duplicateEmail(mixed $data): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |





***

### getAdvancedEmailById



```php
public getAdvancedEmailById(mixed $id): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### createEmail



```php
public createEmail(mixed $data, mixed $receiver_cc = null, mixed $receiver_bcc = null, mixed $letters = null, mixed $documents = null, mixed $tags = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |
| `$receiver_cc` | **mixed** |  |
| `$receiver_bcc` | **mixed** |  |
| `$letters` | **mixed** |  |
| `$documents` | **mixed** |  |
| `$tags` | **mixed** |  |





***

### updateEmail



```php
public updateEmail(mixed $id, mixed $data, mixed $receiver_cc = null, mixed $receiver_bcc = null, mixed $letters = null, mixed $documents = null, mixed $tags = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$data` | **mixed** |  |
| `$receiver_cc` | **mixed** |  |
| `$receiver_bcc` | **mixed** |  |
| `$letters` | **mixed** |  |
| `$documents` | **mixed** |  |
| `$tags` | **mixed** |  |





***

### getEmailTypes



```php
public getEmailTypes(): false
```












***

### getEmailCategories



```php
public getEmailCategories(): false
```












***

### getStatus



```php
public getStatus(): false
```












***

### getTriggersByProgramId



```php
public getTriggersByProgramId(mixed $pid): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** |  |





***

### getTriggerById



```php
public getTriggerById(mixed $tid): object|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tid` | **mixed** |  |





***

### createTrigger



```php
public createTrigger(mixed $trigger, mixed $user): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$trigger` | **mixed** |  |
| `$user` | **mixed** |  |





***

### updateTrigger



```php
public updateTrigger(mixed $tid, mixed $trigger): false|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tid` | **mixed** |  |
| `$trigger` | **mixed** |  |





***

### removeTrigger



```php
public removeTrigger(mixed $tid): false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tid` | **mixed** |  |





***

### getEmailsFromFabrikIds



```php
public getEmailsFromFabrikIds(mixed $ids, mixed $fnum = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |
| `$fnum` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### checkUnpublishedTags



```php
public checkUnpublishedTags(mixed $content): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$content` | **mixed** |  |





***

### sendEmailNoFnum



```php
public sendEmailNoFnum(mixed $email_address, mixed $email, mixed $post = null, mixed $user_id = null, mixed $attachments = [], mixed $fnum = null, mixed $log_email = true): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$email_address` | **mixed** |  |
| `$email` | **mixed** |  |
| `$post` | **mixed** |  |
| `$user_id` | **mixed** |  |
| `$attachments` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$log_email` | **mixed** |  |





***

### sendEmail



```php
public sendEmail(mixed $fnum, mixed $email_id, mixed $post = null, mixed $attachments = [], mixed $bcc = false, mixed $sender_id = null, mixed $user = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$email_id` | **mixed** |  |
| `$post` | **mixed** |  |
| `$attachments` | **mixed** |  |
| `$bcc` | **mixed** |  |
| `$sender_id` | **mixed** |  |
| `$user` | **mixed** |  |





***


***
> Automatically generated on 2024-08-02
