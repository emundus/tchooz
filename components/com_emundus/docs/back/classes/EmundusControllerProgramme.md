***

# EmundusControllerProgramme

campaign Controller



* Full name: `\EmundusControllerProgramme`
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

### m_programme



```php
private $m_programme
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

### getprogrammes



```php
public getprogrammes(): mixed
```













***

### addprogrammes



```php
public addprogrammes(): mixed
```













***

### editprogrammes



```php
public editprogrammes(): mixed
```













***

### favorite



```php
public favorite(): mixed
```













***

### unfavorite



```php
public unfavorite(): mixed
```













***

### getallprogramforfilter



```php
public getallprogramforfilter(): mixed
```













***

### getallprogram



```php
public getallprogram(): mixed
```













***

### getallsessions



```php
public getallsessions(): mixed
```













***

### getprogramcount



```php
public getprogramcount(): mixed
```













***

### getprogrambyid



```php
public getprogrambyid(): mixed
```













***

### createprogram



```php
public createprogram(): mixed
```













***

### updateprogram



```php
public updateprogram(): mixed
```













***

### deleteprogram



```php
public deleteprogram(): mixed
```













***

### unpublishprogram



```php
public unpublishprogram(): mixed
```













***

### publishprogram



```php
public publishprogram(): mixed
```













***

### getprogramcategories



```php
public getprogramcategories(): mixed
```













***

### getmanagers



```php
public getmanagers(): mixed
```













***

### getevaluators



```php
public getevaluators(): mixed
```













***

### affectusertogroup



```php
public affectusertogroup(): mixed
```













***

### affectuserstogroup



```php
public affectuserstogroup(): mixed
```













***

### removefromgroup



```php
public removefromgroup(): mixed
```













***

### getusers



```php
public getusers(): mixed
```













***

### updatevisibility



```php
public updatevisibility(): mixed
```













***

### getevaluationgrid



```php
public getevaluationgrid(): mixed
```













***

### getgridsmodel



```php
public getgridsmodel(): mixed
```













***

### creategrid



```php
public creategrid(): mixed
```













***

### deletegrid



```php
public deletegrid(): mixed
```













***

### affectgrouptoprogram



```php
public affectgrouptoprogram(): mixed
```













***

### deletegroupfromprogram



```php
public deletegroupfromprogram(): mixed
```













***

### getgroupsbyprograms



```php
public getgroupsbyprograms(): mixed
```













***

### getcampaignsbyprogram



```php
public getcampaignsbyprogram(): mixed
```













***


***
> Last updated on 20/08/2024
