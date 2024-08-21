***

# EmundusControllerGroups

eMundus Component Controller



* Full name: `\EmundusControllerGroups`
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

### setAssessor



```php
public setAssessor(mixed $reqids = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$reqids` | **mixed** |  |






***

### unsetAssessor



```php
public unsetAssessor(mixed $reqids = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$reqids` | **mixed** |  |






***

### delassessor



```php
public delassessor(): mixed
```













***

### defaultEmail



```php
public defaultEmail(mixed $reqids = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$reqids` | **mixed** |  |






***

### customEmail



```php
public customEmail(): mixed
```













***

### addgroups



```php
public addgroups(): mixed
```













***


***
> Last updated on 20/08/2024
