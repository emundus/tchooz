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
public display(bool $cachable = false, bool $urlparams = false): \EmundusControllerApplication
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

Delete an applicant attachment by id (one by one)

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

Delete a comment

```php
public deletecomment(): mixed
```












***

### deletetag

Delete an application tag

```php
public deletetag(): mixed
```












***

### deletetraining

Delete training

```php
public deletetraining(): mixed
```












***

### getapplicationmenu

Get menus availables for an application file

```php
public getapplicationmenu(): mixed
```












***

### deleteattachement

Delete attachments by their ids and for a specific fnum

```php
public deleteattachement(): mixed
```












***

### exportpdf

Export an application file to PDF

```php
public exportpdf(): mixed
```












***

### updateaccess

Update access for an application file

```php
public updateaccess(): mixed
```












***

### deleteaccess

Remove access for an application file

```php
public deleteaccess(): mixed
```












***

### attachment_validation

Update validation state of an attachment for an application file

```php
public attachment_validation(): mixed
```












***

### getuserattachments

Get attachments for a specific user

```php
public getuserattachments(): mixed
```












***

### getattachmentsbyfnum

Get attachments for a specific fnum

```php
public getattachmentsbyfnum(): mixed
```












***

### updateattachment

Update file of an attachment for a specific fnum

```php
public updateattachment(): mixed
```












***

### getform

Get datas of an application for a specific fnum, can be filtered by form (profile_id)

```php
public getform(): mixed
```












***

### getattachmentpreview

Load a preview of an attachment

```php
public getattachmentpreview(): mixed
```












***

### reorderapplications

Reorder applications of a user

```php
public reorderapplications(): mixed
```












***

### createtab

Create a tab to group applications for the logged user

```php
public createtab(): mixed
```












***

### gettabs

Get tabs of the logged user

```php
public gettabs(): mixed
```












***

### updatetabs

Update tabs of the logged user

```php
public updatetabs(): mixed
```












***

### deletetab

Delete a tab of the logged user

```php
public deletetab(): mixed
```












***

### copyfile

Copy a file of the logged user to another campaign

```php
public copyfile(): mixed
```












***

### movetotab

Move an application file of the logged user to another tab

```php
public movetotab(): mixed
```












***

### renamefile

Rename an application file of the logged user

```php
public renamefile(): mixed
```












***

### getcampaignsavailableforcopy

Get campaigns available for copy for an application file of the logged user

```php
public getcampaignsavailableforcopy(): mixed
```












***

### filterapplications

Filter applications by order by and filter by

```php
public filterapplications(): mixed
```












***

### applicantcustomaction

Execute a custom action on an application file of the logged user

```php
public applicantcustomaction(): mixed
```












***


***
> Automatically generated on 2024-08-20
