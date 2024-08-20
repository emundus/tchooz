***

# EmundusControllerUsers

users Controller



* Full name: `\EmundusControllerUsers`
* Parent class: [`JControllerLegacy`](./JControllerLegacy.md)



## Properties


### app



```php
protected $app
```






***

### euser



```php
private $euser
```






***

### user



```php
private ?\Joomla\CMS\User\User $user
```






***

## Methods


### __construct

Constructor.

```php
public __construct(array $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An optional associative array of configuration settings. |





**See Also:**

* \JController - 

***

### display

Method to display a view.

```php
public display(bool $cachable = false, bool $urlparams = false): \DisplayController
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cachable` | **bool** | If true, the view output will be cached. |
| `$urlparams` | **bool** | An array of safe URL parameters and their variable types.<br />@see        \Joomla\CMS\Filter\InputFilter::clean() for valid values. |


**Return Value:**

This object to support chaining.




***

### adduser



```php
public adduser(): mixed
```












***

### delincomplete



```php
public delincomplete(): mixed
```












***

### delrefused



```php
public delrefused(): mixed
```












***

### delnonevaluated



```php
public delnonevaluated(): mixed
```












***

### archive



```php
public archive(): mixed
```












***

### lastSavedFilter



```php
public lastSavedFilter(): mixed
```












***

### getConstraintsFilter



```php
public getConstraintsFilter(): mixed
```












***

### export_selected_xls



```php
public export_selected_xls(): mixed
```












***

### export_account_to_xls



```php
public export_account_to_xls(mixed $reqids = array(), mixed $el = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$reqids` | **mixed** |  |
| `$el` | **mixed** |  |





***

### export_zip



```php
public export_zip(): mixed
```












***

### addsession



```php
public addsession(): mixed
```












***

### clear



```php
public clear(): mixed
```












***

### setfilters



```php
public setfilters(): mixed
```












***

### loadfilters



```php
public loadfilters(): mixed
```












***

### order



```php
public order(): mixed
```












***

### setlimit



```php
public setlimit(): mixed
```












***

### savefilters



```php
public savefilters(): mixed
```












***

### deletefilters



```php
public deletefilters(): mixed
```












***

### setlimitstart



```php
public setlimitstart(): mixed
```












***

### addgroup



```php
public addgroup(): mixed
```












***

### changeblock



```php
public changeblock(): mixed
```












***

### changeactivation



```php
public changeactivation(): mixed
```












***

### affectgroups



```php
public affectgroups(): mixed
```












***

### edituser



```php
public edituser(): mixed
```












***

### deleteusers



```php
public deleteusers(): mixed
```












***

### setgrouprights



```php
public setgrouprights(): mixed
```












***

### ldapsearch

Search the LDAP for a user to add.

```php
public ldapsearch(): mixed
```












***

### passrequest

Method to request a password reset. Taken from Joomla and adapted for eMundus.

```php
public passrequest(): bool
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getuserbyid



```php
public getuserbyid(): mixed
```












***

### getUserNameById



```php
public getUserNameById(): mixed
```












***

### getattachmentaccessrights



```php
public getattachmentaccessrights(): mixed
```












***

### getprofileform



```php
public getprofileform(): mixed
```












***

### getprofilegroups



```php
public getprofilegroups(): mixed
```












***

### getprofileelements



```php
public getprofileelements(): mixed
```












***

### getprofileattachments



```php
public getprofileattachments(): mixed
```












***

### getprofileattachmentsallowed



```php
public getprofileattachmentsallowed(): mixed
```












***

### uploaddefaultattachment



```php
public uploaddefaultattachment(): mixed
```












***

### deleteprofileattachment



```php
public deleteprofileattachment(): mixed
```












***

### uploadprofileattachmenttofile



```php
public uploadprofileattachmenttofile(): mixed
```












***

### uploadfileattachmenttoprofile



```php
public uploadfileattachmenttoprofile(): mixed
```












***

### updateprofilepicture



```php
public updateprofilepicture(): mixed
```












***

### activation



```php
public activation(): mixed
```












***

### updateemundussession



```php
public updateemundussession(): mixed
```












***

### addapplicantprofile



```php
public addapplicantprofile(): mixed
```












***

### affectjoomlagroups



```php
public affectjoomlagroups(): mixed
```












***

### activation_anonym_user



```php
public activation_anonym_user(): mixed
```












***

### getCurrentUser



```php
public getCurrentUser(): mixed
```












***

### getcurrentprofile



```php
public getcurrentprofile(): mixed
```












***

### exportusers



```php
public exportusers(): void
```











**Throws:**

- [`Exception`](./Exception.md)



***


***
> Last updated on 20/08/2024
