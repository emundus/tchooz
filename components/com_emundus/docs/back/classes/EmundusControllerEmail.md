***

# EmundusControllerEmail

eMundus Component Controller



* Full name: `\EmundusControllerEmail`
* Parent class: [`JControllerLegacy`](./JControllerLegacy.md)



## Properties


### app



```php
protected $app
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

### m_emails



```php
private $m_emails
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

### applicantEmail



```php
public applicantEmail(): mixed
```












***

### getTemplate



```php
public getTemplate(): mixed
```












***

### sendmail_expert



```php
public sendmail_expert(): mixed
```












***

### getallemail

Get emails filtered

```php
public getallemail(): mixed
```












***

### deleteemail



```php
public deleteemail(): mixed
```












***

### unpublishemail



```php
public unpublishemail(): mixed
```












***

### publishemail



```php
public publishemail(): mixed
```












***

### duplicateemail



```php
public duplicateemail(): mixed
```












***

### createemail



```php
public createemail(): mixed
```












***

### updateemail



```php
public updateemail(): mixed
```












***

### getemailbyid



```php
public getemailbyid(): mixed
```












***

### getemailcategories



```php
public getemailcategories(): mixed
```












***

### getemailtypes



```php
public getemailtypes(): mixed
```












***

### getstatus



```php
public getstatus(): mixed
```












***

### gettriggersbyprogram



```php
public gettriggersbyprogram(): mixed
```












***

### gettriggerbyid



```php
public gettriggerbyid(): mixed
```












***

### createtrigger



```php
public createtrigger(): mixed
```












***

### updatetrigger



```php
public updatetrigger(): mixed
```












***

### removetrigger



```php
public removetrigger(): mixed
```












***


***
> Automatically generated on 2024-08-19
