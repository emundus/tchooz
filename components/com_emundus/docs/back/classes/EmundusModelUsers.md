***

# EmundusModelUsers





* Full name: `\EmundusModelUsers`
* Parent class: [`JModelList`](./JModelList.md)



## Properties


### _total



```php
public $_total
```






***

### _pagination



```php
public $_pagination
```






***

### data



```php
protected $data
```






***

### db



```php
private $db
```






***

### app



```php
private $app
```






***

### user



```php
private $user
```






***

## Methods


### __construct

Constructor

```php
public __construct(): mixed
```












***

### _buildContentOrderBy



```php
public _buildContentOrderBy(): mixed
```












***

### _buildQuery



```php
public _buildQuery(): mixed
```












***

### getUsers



```php
public getUsers(mixed $limit_start = null, mixed $limit = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$limit_start` | **mixed** |  |
| `$limit` | **mixed** |  |





***

### getProfiles



```php
public getProfiles(): mixed
```












***

### getProfilesByIDs



```php
public getProfilesByIDs(mixed $ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |





***

### getEditProfiles



```php
public getEditProfiles(): mixed
```












***

### getApplicantProfiles



```php
public getApplicantProfiles(): mixed
```












***

### getUsersProfiles



```php
public getUsersProfiles(): mixed
```












***

### getUserByEmail



```php
public getUserByEmail(mixed $email): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$email` | **mixed** |  |





***

### getEmundusUserByEmail



```php
public getEmundusUserByEmail(mixed $email): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$email` | **mixed** |  |





***

### getProfileIDByCampaignID



```php
public getProfileIDByCampaignID(mixed $cid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |





***

### getCurrentUserProfile



```php
public getCurrentUserProfile(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getProfileDetails



```php
public getProfileDetails(mixed $profile_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$profile_id` | **mixed** |  |





***

### changeCurrentUserProfile



```php
public changeCurrentUserProfile(mixed $uid, mixed $pid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |
| `$pid` | **mixed** |  |





***

### getUniversities



```php
public getUniversities(): mixed
```












***

### getGroups



```php
public getGroups(): mixed
```












***

### getUsersIntranetGroups



```php
public getUsersIntranetGroups(mixed $uid, mixed $return = &#039;AssocList&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |
| `$return` | **mixed** |  |





***

### getLascalaIntranetGroups



```php
public getLascalaIntranetGroups(mixed $uid = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getCampaigns



```php
public getCampaigns(): mixed
```












***

### getCampaignsPublished



```php
public getCampaignsPublished(): mixed
```












***

### getAllCampaigns



```php
public getAllCampaigns(): mixed
```












***

### getCampaignsCandidature



```php
public getCampaignsCandidature(mixed $aid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |





***

### getUserListWithSchoolyear



```php
public getUserListWithSchoolyear(mixed $schoolyears): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$schoolyears` | **mixed** |  |





***

### getUserListWithCampaign



```php
public getUserListWithCampaign(mixed $campaign): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign` | **mixed** |  |





***

### compareCampaignANDSchoolyear



```php
public compareCampaignANDSchoolyear(mixed $campaign, mixed $schoolyear): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign` | **mixed** |  |
| `$schoolyear` | **mixed** |  |





***

### getCurrentCampaign



```php
public getCurrentCampaign(): mixed
```












***

### getCurrentCampaignsID



```php
public getCurrentCampaignsID(): mixed
```












***

### getCurrentCampaigns



```php
public getCurrentCampaigns(): mixed
```












***

### getProgramme



```php
public getProgramme(): mixed
```












***

### getNewsletter



```php
public getNewsletter(): mixed
```












***

### getGroupEval



```php
public getGroupEval(): mixed
```












***

### getGroupsEval



```php
public getGroupsEval(): mixed
```












***

### getUserListWithGroupsEval



```php
public getUserListWithGroupsEval(mixed $groups): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groups` | **mixed** |  |





***

### getUsersGroups



```php
public getUsersGroups(): mixed
```












***

### getSchoolyears



```php
public getSchoolyears(): mixed
```












***

### getTotal



```php
public getTotal(): mixed
```












***

### getPagination



```php
public getPagination(): mixed
```












***

### getPageNavigation



```php
public getPageNavigation(): string
```












***

### getForm

Method to get the registration form.

```php
public getForm(array $data = array(), bool $loadData = true): \JForm|false
```

The base form is loaded from XML and then an event is fired
for users plugins to extend the form with extra fields.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | An optional array of data for the form to interogate. |
| `$loadData` | **bool** | True if the form is to load its own data (default case), false if not. |


**Return Value:**

A JForm object on success, false on failure




***

### loadFormData

Method to get the data that should be injected in the form.

```php
protected loadFormData(): mixed
```









**Return Value:**

The data for the form.




***

### getData

Method to get the registration form data.

```php
public getData(): mixed
```

The base form data is loaded and then an event is fired
for users plugins to extend the data.







**Return Value:**

Data object on success, false on failure.




***

### adduser

Adds a user to Joomla as well as the eMundus tables.

```php
public adduser(mixed $user, mixed $other_params): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |
| `$other_params` | **mixed** |  |


**Return Value:**

user_id, 0 if failed




***

### addEmundusUser



```php
public addEmundusUser(mixed $user_id, mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$params` | **mixed** |  |





***

### found_usertype



```php
public found_usertype(mixed $acl_aro_groups): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$acl_aro_groups` | **mixed** |  |





***

### getDefaultGroup



```php
public getDefaultGroup(mixed $pid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** |  |





***

### login



```php
public login(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### plainLogin

PLAIN LOGIN

```php
public plainLogin(mixed $credentials, int $redirect = 1): bool|\JException
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$credentials` | **mixed** |  |
| `$redirect` | **int** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### encryptLogin

ENCRYPT LOGIN

```php
public encryptLogin(mixed $credentials, int $redirect = 1): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$credentials` | **mixed** |  |
| `$redirect` | **int** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getFnumsAssoc



```php
public getFnumsAssoc(mixed $action_id, mixed $crud = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$action_id` | **mixed** |  |
| `$crud` | **mixed** |  |





***

### getFnumsGroupAssoc



```php
public getFnumsGroupAssoc(mixed $group_id, mixed $action_id, mixed $crud = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group_id` | **mixed** |  |
| `$action_id` | **mixed** |  |
| `$crud` | **mixed** |  |





***

### getFnumsUserAssoc



```php
public getFnumsUserAssoc(mixed $action_id, mixed $crud = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$action_id` | **mixed** |  |
| `$crud` | **mixed** |  |





***

### getEvalutorByFnums



```php
public getEvalutorByFnums(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getActions



```php
public getActions(mixed $actions = &#039;&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$actions` | **mixed** |  |





***

### setGroupRight



```php
public setGroupRight(mixed $id, mixed $action, mixed $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$action` | **mixed** |  |
| `$value` | **mixed** |  |





***

### addGroup



```php
public addGroup(mixed $gname, mixed $gdesc, mixed $actions, mixed $progs, mixed $returnGid = false): bool|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gname` | **mixed** |  |
| `$gdesc` | **mixed** |  |
| `$actions` | **mixed** |  |
| `$progs` | **mixed** |  |
| `$returnGid` | **mixed** |  |





***

### changeBlock



```php
public changeBlock(mixed $users, mixed $state): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$state` | **mixed** |  |





***

### changeActivation



```php
public changeActivation(mixed $users, mixed $state): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$state` | **mixed** |  |





***

### createParam



```php
public createParam(mixed $param, null $user_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$param` | **mixed** | String The param to be saved in the user account. |
| `$user_id` | **null** |  |





***

### getNonApplicantId



```php
public getNonApplicantId(mixed $users): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |





***

### affectToGroups



```php
public affectToGroups(mixed $users, mixed $groups): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$groups` | **mixed** |  |





***

### affectToJoomlaGroups



```php
public affectToJoomlaGroups(mixed $users, mixed $groups): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |
| `$groups` | **mixed** |  |





***

### getUserInfos



```php
public getUserInfos(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getOnlineUsers



```php
public getOnlineUsers(): mixed
```












***

### getUserGroups



```php
public getUserGroups(mixed $uid, mixed $return = &#039;AssocList&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |
| `$return` | **mixed** |  |





***

### getUserGroupsProgramme

getUserGroupsProgramme

```php
public getUserGroupsProgramme(mixed $uid): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getUserACL



```php
public getUserACL(mixed $uid = null, mixed $fnum = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### getUserGroupsProgrammeAssoc



```php
public getUserGroupsProgrammeAssoc(mixed $uid, mixed $select = &#039;jesgrc.course&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |
| `$select` | **mixed** |  |





***

### getAllCampaignsAssociatedToUser



```php
public getAllCampaignsAssociatedToUser(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getApplicationsAssocToGroups



```php
public getApplicationsAssocToGroups(mixed $gid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### getApplicantsAssoc



```php
public getApplicantsAssoc(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getUserCampaigns



```php
public getUserCampaigns(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getUserOprofiles



```php
public getUserOprofiles(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### countUserEvaluations



```php
public countUserEvaluations(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### countUserDecisions



```php
public countUserDecisions(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### addProfileToUser



```php
public addProfileToUser(mixed $uid, mixed $pid): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** | Int User id |
| `$pid` | **mixed** | Int Profile id |





***

### editUser



```php
public editUser(mixed $user): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |





***

### getGroupDetails



```php
public getGroupDetails(mixed $gid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### getGroupProgs



```php
public getGroupProgs(mixed $gid): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### getGroupsAcl



```php
public getGroupsAcl(mixed $gids): array|bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gids` | **mixed** |  |





***

### getEffectiveGroupsForFnum

This function returns the groups which are linked to the fnum's program OR NO PROGRAM AT ALL.

```php
public getEffectiveGroupsForFnum(mixed $group_ids, mixed $fnum, mixed $strict = false): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group_ids` | **mixed** | array |
| `$fnum` | **mixed** | string |
| `$strict` | **mixed** | bool if true, only the groups linked to the fnum&#039;s program are returned |





***

### getGroupUsers



```php
public getGroupUsers(mixed $gid): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### getMenuList



```php
public getMenuList(mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### getUserActionByFnum



```php
public getUserActionByFnum(mixed $aid, mixed $fnum, mixed $uid, mixed $crud): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$aid` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$uid` | **mixed** |  |
| `$crud` | **mixed** |  |





***

### getGroupActions



```php
public getGroupActions(mixed $gids, mixed $fnum, mixed $aid, mixed $crud): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gids` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$aid` | **mixed** |  |
| `$crud` | **mixed** |  |





***

### setNewPasswd



```php
public setNewPasswd(mixed $uid, mixed $passwd): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** | int user id |
| `$passwd` | **mixed** | string  password to set |





***

### searchLDAP

Connect to LDAP

```php
public searchLDAP(mixed $search): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$search` | **mixed** |  |





***

### getUserById



```php
public getUserById(mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getUserNameById



```php
public getUserNameById(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getUsersById



```php
public getUsersById(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getUsersByIds



```php
public getUsersByIds(mixed $ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |





***

### passwordReset

Method to start the password reset process. Taken from Joomla and modified to send email using template.

```php
public passwordReset(array $data, mixed $subject = &#039;COM_USERS_EMAIL_PASSWORD_RESET_SUBJECT&#039;, mixed $body = &#039;COM_USERS_EMAIL_PASSWORD_RESET_BODY&#039;): object
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | The data expected for the form. |
| `$subject` | **mixed** |  |
| `$body` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getProfileForm



```php
public getProfileForm(): mixed
```












***

### getProfileGroups



```php
public getProfileGroups(mixed $formid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$formid` | **mixed** |  |





***

### getProfileElements



```php
public getProfileElements(mixed $group): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |





***

### saveUser



```php
public saveUser(mixed $user, mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |
| `$uid` | **mixed** |  |





***

### getProfileAttachments



```php
public getProfileAttachments(mixed $user_id, mixed $fnum = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### getProfileAttachmentsAllowed



```php
public getProfileAttachmentsAllowed(): mixed
```












***

### addDefaultAttachment



```php
public addDefaultAttachment(mixed $user_id, mixed $attachment_id, mixed $filename): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$attachment_id` | **mixed** |  |
| `$filename` | **mixed** |  |





***

### deleteProfileAttachment



```php
public deleteProfileAttachment(mixed $id, mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$user_id` | **mixed** |  |





***

### uploadProfileAttachmentToFile



```php
public uploadProfileAttachmentToFile(mixed $fnum, mixed $aids, mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$aids` | **mixed** |  |
| `$uid` | **mixed** |  |





***

### uploadFileAttachmentToProfile



```php
public uploadFileAttachmentToProfile(mixed $fnum, mixed $aid, mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$aid` | **mixed** |  |
| `$uid` | **mixed** |  |





***

### updateProfilePicture



```php
public updateProfilePicture(mixed $user_id, mixed $target_file): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$target_file` | **mixed** |  |





***

### addApplicantProfile



```php
public addApplicantProfile(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### onAfterAnonymUserMapping



```php
public onAfterAnonymUserMapping(mixed $data, mixed $campaign_id, mixed $program_code = &#039;&#039;): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** | must give user_id, email, is_anonym and token |
| `$campaign_id` | **mixed** |  |
| `$program_code` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### connectUserFromToken

Login user from token
Rule: token must have an expiration date

```php
public connectUserFromToken(mixed $token): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$token` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### connectUserFromId



```php
private connectUserFromId(mixed $user_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### checkTokenCorrespondToUser

Assert user_id and token are related

```php
public checkTokenCorrespondToUser(mixed $token, mixed $user_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$token` | **mixed** |  |
| `$user_id` | **mixed** |  |





***

### updateAnonymUserAccount

Activate anonym user
Use email_anonym column from emundus_users found from token and user_id
If user with this email already exists, bind files to this existing user
Else update current user anonym infos

```php
public updateAnonymUserAccount(mixed $token, mixed $user_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$token` | **mixed** |  |
| `$user_id` | **mixed** |  |


**Return Value:**

updated




***

### getUserToken

Retrieve token from user_id
Must stay private to make sure it used in correct context

```php
private getUserToken(mixed $user_id): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### assertNotMaliciousAttemptsUsingConnectViaToken

Make sure no one can brute force via testing token again and again
Is there too much wrong attempts from same IP in last 24h
If so, block adress IP

```php
private assertNotMaliciousAttemptsUsingConnectViaToken(): void
```












***

### generateUserToken

Generate a new token for current user

```php
public generateUserToken(): string
```









**Return Value:**

the new token generated, or empty string if failed




***

### isSamlUser



```php
public isSamlUser(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### getIdentityPhoto



```php
public getIdentityPhoto(mixed $fnum, mixed $applicant_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$applicant_id` | **mixed** |  |





***

### randomPassword



```php
public randomPassword(mixed $len = 8): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$len` | **mixed** |  |





***

### getUserGroupsLabelById



```php
public getUserGroupsLabelById(mixed $uid): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### getColumnsFromProfileForm



```php
public getColumnsFromProfileForm(): array|mixed|null
```












***

### getJoomlaUserColumns



```php
public getJoomlaUserColumns(): object[]
```












***

### getAllInformationsToExport



```php
public getAllInformationsToExport(mixed $uid): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### getUserDetails



```php
public getUserDetails(mixed $uid): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | **mixed** |  |





***

### repairEmundusUser

Check if user is already registered in emundus_users, if not, create it

```php
public repairEmundusUser(mixed $user_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |


**Return Value:**

true if user is already registered or if we are able to create it, false otherwise




***

### convertCsvToXls



```php
public convertCsvToXls(mixed $csv, mixed $nb_cols, mixed $nb_rows, mixed $excel_file_name, mixed $separator = &#039;	&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$csv` | **mixed** |  |
| `$nb_cols` | **mixed** |  |
| `$nb_rows` | **mixed** |  |
| `$excel_file_name` | **mixed** |  |
| `$separator` | **mixed** |  |





***


***
> Automatically generated on 2024-08-02
