***

# EmundusModelChecklist





* Full name: `\EmundusModelChecklist`
* Parent class: [`JModelList`](./JModelList.md)



## Properties


### app



```php
private $app
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

### _need



```php
private $_need
```






***

### _forms



```php
protected $_forms
```






***

### _attachments



```php
private $_attachments
```






***

## Methods


### __construct



```php
public __construct(mixed $student_id = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$student_id` | **mixed** |  |





***

### getGreeting



```php
public getGreeting(): mixed
```












***

### getInstructions



```php
public getInstructions(): mixed
```












***

### getFormsList



```php
public getFormsList(): mixed
```












***

### getAttachmentsList



```php
public getAttachmentsList(): mixed
```












***

### getAttachmentsForCampaignId



```php
public getAttachmentsForCampaignId(mixed $campaign_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **mixed** |  |





***

### getAttachmentsForProfile

Get attachments for a profile
Be aware that this method will dispatch onAfterGetAttachmentsForProfile event
This event can be used to add or remove attachments from the list based on some conditions

```php
public getAttachmentsForProfile(mixed $profile_id, mixed $campaign_id = null): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |
| `$campaign_id` | **mixed** |  |





***

### getNeed



```php
public getNeed(): mixed
```












***

### getSent



```php
public getSent(): mixed
```












***

### getResult



```php
public getResult(): mixed
```












***

### getApplicant



```php
public getApplicant(): mixed
```












***

### getIsOtherActiveCampaign



```php
public getIsOtherActiveCampaign(): mixed
```












***

### getConfirmUrl



```php
public getConfirmUrl(mixed $profile = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile` | **mixed** |  |





***

### setDelete



```php
public setDelete(mixed $can_be_deleted, mixed $student = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$can_be_deleted` | **mixed** |  |
| `$student` | **mixed** |  |





***

### formatFileName



```php
public formatFileName(string $file, string $fnum, array $post = []): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **string** |  |
| `$fnum` | **string** |  |
| `$post` | **array** |  |





***


***
> Last updated on 20/08/2024
