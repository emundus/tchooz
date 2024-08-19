***

# EmundusControllerApplication

Emundus Application Class



* Full name: `\EmundusControllerApplication`
* Parent class: [`BaseController`](./Joomla/CMS/MVC/Controller/BaseController.md)



## Properties


### _user

User object.

```php
private \Joomla\CMS\User\User|\JUser|mixed|null $_user
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

### delete_attachments

Delete applicant attachments

```php
public delete_attachments(): mixed
```












***

### delete_attachment

Delete an applicant attachment (one by one)

```php
public delete_attachment(): mixed
```












***

### upload_attachment

Upload an applicant attachment (one by one)

```php
public upload_attachment(): mixed
```












***

### editcomment

Edit a comment

```php
public editcomment(): mixed
```












***

### deletecomment



```php
public deletecomment(): mixed
```












***

### deletetag



```php
public deletetag(): mixed
```












***

### deletetraining



```php
public deletetraining(): mixed
```












***

### getapplicationmenu



```php
public getapplicationmenu(): mixed
```












***

### deleteattachement



```php
public deleteattachement(): mixed
```












***

### exportpdf



```php
public exportpdf(): mixed
```












***

### updateaccess



```php
public updateaccess(): mixed
```












***

### deleteaccess



```php
public deleteaccess(): mixed
```












***

### attachment_validation



```php
public attachment_validation(): mixed
```












***

### getuserattachments



```php
public getuserattachments(): mixed
```












***

### getattachmentsbyfnum



```php
public getattachmentsbyfnum(): mixed
```












***

### updateattachment



```php
public updateattachment(): mixed
```












***

### getform



```php
public getform(): mixed
```












***

### getattachmentpreview



```php
public getattachmentpreview(): mixed
```












***

### reorderapplications



```php
public reorderapplications(): mixed
```












***

### createtab



```php
public createtab(): mixed
```












***

### gettabs



```php
public gettabs(): mixed
```












***

### updatetabs



```php
public updatetabs(): mixed
```












***

### deletetab



```php
public deletetab(): mixed
```












***

### copyfile



```php
public copyfile(): mixed
```












***

### movetotab



```php
public movetotab(): mixed
```












***

### renamefile



```php
public renamefile(): mixed
```












***

### getcampaignsavailableforcopy



```php
public getcampaignsavailableforcopy(): mixed
```












***

### filterapplications



```php
public filterapplications(): mixed
```












***

### applicantcustomaction



```php
public applicantcustomaction(): mixed
```












***


***
> Automatically generated on 2024-08-19
