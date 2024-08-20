***

# EmundusControllerCampaign

Campaign Controller



* Full name: `\EmundusControllerCampaign`
* Parent class: [`JControllerLegacy`](./JControllerLegacy.md)



## Properties


### app



```php
protected $app
```






***

### _user



```php
private $_user
```






***

### m_campaign



```php
private $m_campaign
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

### clear



```php
public clear(): mixed
```












***

### setCampaign



```php
public setCampaign(): mixed
```












***

### addcampaigns

Add campaign for Ametys sync

```php
public addcampaigns(): mixed
```












***

### getcampaignsbyprogram

Gets all campaigns linked to a program code

```php
public getcampaignsbyprogram(): mixed
```












***

### getcampaignsbyprogramme

Get the number of campaigns by program

```php
public getcampaignsbyprogramme(): mixed
```












***

### getallcampaign

Get the campaigns's list filtered

```php
public getallcampaign(): mixed
```












***

### goToCampaign



```php
public goToCampaign(): mixed
```












***

### deletecampaign

Delete one or multiple campaigns

```php
public deletecampaign(): mixed
```












***

### unpublishcampaign

Unpublish one or multiple campaigns

```php
public unpublishcampaign(): mixed
```












***

### publishcampaign

Publish one or multiple campaigns

```php
public publishcampaign(): mixed
```












***

### duplicatecampaign

Duplicate one or multiple campaigns

```php
public duplicatecampaign(): mixed
```












***

### getyears

Get teaching_unity available

```php
public getyears(): mixed
```












***

### createcampaign

Create a campaign

```php
public createcampaign(): mixed
```












***

### updatecampaign

Update a campaign

```php
public updatecampaign(): mixed
```












***

### getcampaignbyid

Get a campaign by id

```php
public getcampaignbyid(): mixed
```












***

### getcreatedcampaign

Return the created campaign

```php
public getcreatedcampaign(): mixed
```












***

### updateprofile

Affect a profile(form) to a campaign

```php
public updateprofile(): mixed
```












***

### getcampaignstoaffect

Get campaigns without profile affected and not finished

```php
public getcampaignstoaffect(): mixed
```












***

### getcampaignstoaffectbyterm

Get campaigns with term filter in name and description

```php
public getcampaignstoaffectbyterm(): mixed
```












***

### createdocument

Add a new document to form

```php
public createdocument(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### updatedocument

Update form document

```php
public updatedocument(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### updatedocumentmandatory



```php
public updatedocumentmandatory(): mixed
```












***

### updateDocumentFalang

Update translations of documents

```php
public updateDocumentFalang(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getDocumentFalang

Get translations of documents

```php
public getDocumentFalang(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getdocumentsdropfiles

Get Dropfiles documents linked to a campaign

```php
public getdocumentsdropfiles(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### deletedocumentdropfile

Delete Dropfile document

```php
public deletedocumentdropfile(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### editdocumentdropfile

Edit a Dropfile document

```php
public editdocumentdropfile(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### updateorderdropfiledocuments

Update the order of Dropfiles documents

```php
public updateorderdropfiledocuments(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getdocumentsform

Get documents link to form by campaign (by the module)

```php
public getdocumentsform(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### editdocumentform

Update a document available in form view

```php
public editdocumentform(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### deletedocumentform

Delete a document from form view

```php
public deletedocumentform(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### pincampaign



```php
public pincampaign(): mixed
```












***

### unpincampaign



```php
public unpincampaign(): mixed
```












***

### getallitemsalias



```php
public getallitemsalias(): mixed
```












***

### getProgrammeByCampaignID



```php
public getProgrammeByCampaignID(): mixed
```












***

### getcampaignmoreformurl



```php
public getcampaignmoreformurl(): mixed
```












***


***
> Automatically generated on 2024-08-20
