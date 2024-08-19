***

# EmundusControllerExport_select_columns

eMundus Component Controller



* Full name: `\EmundusControllerExport_select_columns`
* Parent class: [`JControllerLegacy`](./JControllerLegacy.md)



## Properties


### app



```php
protected $app
```






***

## Methods


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

### getformtags



```php
public getformtags(): mixed
```












***

### getalltags

Gets all eMundus Tags from tags_table

```php
public getalltags(): mixed
```












***


***
> Automatically generated on 2024-08-19
