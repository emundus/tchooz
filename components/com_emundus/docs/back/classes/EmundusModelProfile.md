***

# EmundusModelProfile





* Full name: `\EmundusModelProfile`
* Parent class: [`JModelList`](./JModelList.md)



## Properties


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

### getProfile

Gets the greeting

```php
public getProfile(mixed $p): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$p` | **mixed** |  |


**Return Value:**

The greeting to be displayed to the user





***

### getApplicantsProfiles



```php
public getApplicantsProfiles(): mixed
```













***

### getApplicantsProfilesArray



```php
public getApplicantsProfilesArray(): array
```









**Return Value:**

of profile_id for all applicant profiles





***

### getUserProfiles



```php
public getUserProfiles(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |






***

### getProfileByApplicant



```php
public getProfileByApplicant(mixed $aid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |






***

### affectNoProfile



```php
public affectNoProfile(mixed $aid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |






***

### getFullProfileByFnum

This is used to replace getProfileByApplicant when using an fnum.

```php
public getFullProfileByFnum(mixed $fnum): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getProfileById



```php
public getProfileById(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getProfileByFnum



```php
public getProfileByFnum(mixed $fnum): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getCurrentProfile



```php
public getCurrentProfile(mixed $aid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |






***

### getAttachments



```php
public getAttachments(mixed $p, mixed $mandatory = false): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$p` | **mixed** |  |
| `$mandatory` | **mixed** |  |






***

### getForms



```php
public getForms(mixed $p): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$p` | **mixed** |  |






***

### isProfileUserSet



```php
public isProfileUserSet(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |






***

### updateProfile



```php
public updateProfile(mixed $uid, mixed $campaign): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |
| `$campaign` | **mixed** |  |






***

### getCurrentCampaignByApplicant



```php
public getCurrentCampaignByApplicant(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |






***

### getCurrentIncompleteCampaignByApplicant



```php
public getCurrentIncompleteCampaignByApplicant(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |






***

### getCurrentCompleteCampaignByApplicant



```php
public getCurrentCompleteCampaignByApplicant(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |






***

### getCurrentCampaignInfoByApplicant



```php
public getCurrentCampaignInfoByApplicant(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |






***

### getCampaignInfoByFnum



```php
public getCampaignInfoByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getCampaignById



```php
public getCampaignById(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getProfileByCampaign



```php
public getProfileByCampaign(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getWorkflowProfilesByCampaign



```php
public getWorkflowProfilesByCampaign(mixed $campaign_id): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **mixed** |  |






***

### getProfileByStatus



```php
public getProfileByStatus(mixed $fnum): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** | string |






***

### getProfileByStep



```php
public getProfileByStep(mixed $step): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$step` | **mixed** |  |






***

### getProfileByMenu



```php
public getProfileByMenu(mixed $menu): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$menu` | **mixed** |  |






***

### getFabrikListByIds



```php
public getFabrikListByIds(mixed $flist): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$flist` | **mixed** |  |






***

### getFabrikFormByList



```php
public getFabrikFormByList(mixed $list): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$list` | **mixed** |  |






***

### getFabrikGroupByList



```php
public getFabrikGroupByList(mixed $glist): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$glist` | **mixed** |  |






***

### getFabrikElementById



```php
public getFabrikElementById(mixed $eid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |






***

### getDataFromElementName



```php
public getDataFromElementName(mixed $element, mixed $fnum, mixed $user): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$user` | **mixed** |  |






***

### getProfileIDByCourse

Gets the list of profiles from array of programmes

```php
public getProfileIDByCourse(array $code = array(), array $camps = array()): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **array** | list of programmes code |
| `$camps` | **array** | list of campaigns |


**Return Value:**

The profile IDs found





***

### getProfileIDByCampaign

Gets the list of profiles from array of programmes

```php
public getProfileIDByCampaign(array $campaign_id): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **array** |  |


**Return Value:**

The profile list for the campaigns





***

### getProfilesIDByCampaign

Gets the list of profiles from array of programmes

```php
public getProfilesIDByCampaign(array $campaign_id, mixed $return = &#039;column&#039;): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign_id` | **array** |  |
| `$return` | **mixed** |  |


**Return Value:**

The profile list for the campaigns





***

### getProfileIDByCampaigns



```php
public getProfileIDByCampaigns(mixed $campaigns, mixed $codes): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaigns` | **mixed** |  |
| `$codes` | **mixed** |  |






***

### getFnumDetails



```php
public getFnumDetails(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getCandidatureByFnum



```php
public getCandidatureByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### isApplicationDeclared



```php
public isApplicationDeclared(mixed $aid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |






***

### getApplicantFnums

Get fnums for applicants

```php
public getApplicantFnums(int $aid, int $submitted = null, \datetime $start_date = null, \datetime $end_date = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **int** | Applicant ID |
| `$submitted` | **int** | Submitted application |
| `$start_date` | **\datetime** | campaigns as started after |
| `$end_date` | **\datetime** | campaigns as ended before |




**Throws:**

- [`Exception`](./Exception.md)




***

### initEmundusSession

Creates an object in the session that acts as a replacement for the default Joomla user

```php
public initEmundusSession(null $fnum = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **null** |  |






***

### getEmundusUser

Returns an object based on supplied user_id that acts as a replacement for the default Joomla user method

```php
public getEmundusUser(mixed $user_id): \stdClass
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)




***

### getHikashopMenu



```php
public getHikashopMenu(mixed $profile): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile` | **mixed** |  |






***

### getFilesMenuPathByProfile



```php
public getFilesMenuPathByProfile(mixed $profile_id): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |






***

### checkIsAnonymUser



```php
private checkIsAnonymUser(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |






***

### getAnonymSessionToken



```php
private getAnonymSessionToken(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |






***


***
> Last updated on 20/08/2024
