***

# EmundusControllerDecision

eMundus Component Controller



* Full name: `\EmundusControllerDecision`
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

### _db



```php
private $_db
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

### applicantEmail



```php
public applicantEmail(): mixed
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

### getadvfilters



```php
public getadvfilters(): mixed
```












***

### addcomment



```php
public addcomment(): mixed
```












***

### getevsandgroups



```php
public getevsandgroups(): mixed
```












***

### gettags



```php
public gettags(): mixed
```












***

### tagfile

Add a tag to an application

```php
public tagfile(): mixed
```












***

### deletetags



```php
public deletetags(): mixed
```












***

### share



```php
public share(): mixed
```












***

### getstate



```php
public getstate(): mixed
```












***

### updatestate



```php
public updatestate(): mixed
```












***

### unlinkevaluators



```php
public unlinkevaluators(): mixed
```












***

### getfnuminfos



```php
public getfnuminfos(): mixed
```












***

### deletefile



```php
public deletefile(): mixed
```












***

### getformelem



```php
public getformelem(): mixed
```












***

### pdf



```php
public pdf(): mixed
```












***

### pdf_decision



```php
public pdf_decision(): mixed
```












***

### return_bytes



```php
public return_bytes(mixed $val): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$val` | **mixed** |  |





***

### sortArrayByArray



```php
public sortArrayByArray(mixed $array, mixed $orderArray): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$array` | **mixed** |  |
| `$orderArray` | **mixed** |  |





***

### sortObjectByArray



```php
public sortObjectByArray(mixed $object, mixed $orderArray): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$object` | **mixed** |  |
| `$orderArray` | **mixed** |  |





***

### create_file_csv



```php
public create_file_csv(): mixed
```












***

### getfnums_csv



```php
public getfnums_csv(): mixed
```












***

### getcolumn



```php
public getcolumn(mixed $elts): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elts` | **mixed** |  |





***

### generate_array



```php
public generate_array(): mixed
```












***

### get_mime_type



```php
public get_mime_type(mixed $filename, mixed $mimePath = &#039;../etc&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filename` | **mixed** |  |
| `$mimePath` | **mixed** |  |





***

### download



```php
public download(): mixed
```












***

### export_zip



```php
public export_zip(mixed $fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***


***
> Automatically generated on 2024-08-20
