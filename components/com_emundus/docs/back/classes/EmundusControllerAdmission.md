***

# EmundusControllerAdmission

Emundus Admission Class



* Full name: `\EmundusControllerAdmission`
* Parent class: [`BaseController`](./Joomla/CMS/MVC/Controller/BaseController.md)
* **Warning:** this class is **deprecated**. This means that this class will likely be removed in a future version.



## Properties


### user

User object

```php
private \Joomla\CMS\User\User|\JUser|mixed|null $user
```






***

### _db

Database object

```php
private \JDatabaseDriver|\Joomla\Database\DatabaseDriver|null $_db
```






***

### session

Session object

```php
private \Joomla\Session\SessionInterface|\JSession $session
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
public display(bool $cachable = false, bool $urlparams = false): \EmundusControllerAdmission
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

Clear session and reinit values by default

```php
public clear(): mixed
```












***

### setfilters

Set filters of admission view

```php
public setfilters(): mixed
```












***

### loadfilters

Load filters of admission view

```php
public loadfilters(): mixed
```












***

### order

Reorder the list of applications in admission view

```php
public order(): mixed
```












***

### setlimit

Set the limit of applications in admission view

```php
public setlimit(): mixed
```












***

### savefilters

Save a custom filter

```php
public savefilters(): mixed
```












***

### deletefilters

Delete a saved filter

```php
public deletefilters(): mixed
```












***

### setlimitstart

Set the start of the list of applications in admission view

```php
public setlimitstart(): mixed
```












***

### getadvfilters

Get the list of advanced filters

```php
public getadvfilters(): mixed
```












***

### addcomment

Add a comment

```php
public addcomment(): mixed
```












***

### getevsandgroups

Get list of evaluation groups and users

```php
public getevsandgroups(): mixed
```












***

### gettags

Get list of tags for applications

```php
public gettags(): mixed
```












***

### tagfile

Add a tag to applications

```php
public tagfile(): mixed
```












***

### deletetags

Delete a tag from applications

```php
public deletetags(): mixed
```












***

### share

Share files with groups or/and users

```php
public share(): mixed
```












***

### getstate

Get list of status available for applications

```php
public getstate(): mixed
```












***

### updatestate

Update the status of applications

```php
public updatestate(): mixed
```












***

### unlinkevaluators

Unlink evaluators from a single application file

```php
public unlinkevaluators(): mixed
```












***

### getfnuminfos

Get details of a single application file

```php
public getfnuminfos(): mixed
```












***

### deletefile

Move an application file to trash

```php
public deletefile(): mixed
```












***

### getformelem

Get elements from a program

```php
public getformelem(): mixed
```












***

### pdf_admission

Export a single application in PDF format

```php
public pdf_admission(): mixed
```












***

### create_file_csv

Export applications in CSV format

```php
public create_file_csv(): mixed
```












***

### getfnums_csv

Prepare the list of applications to export in CSV format

```php
public getfnums_csv(): mixed
```












***

### getcolumn

Get column from elements for CSV export

```php
public getcolumn(mixed $elts): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elts` | **mixed** |  |





***

### generate_array

Generate array to export in CSV format

```php
public generate_array(): mixed
```












***

### get_mime_type

Get mime type of a file

```php
public get_mime_type(mixed $filename, mixed $mimePath = &#039;../etc&#039;): false|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filename` | **mixed** |  |
| `$mimePath` | **mixed** |  |





***

### download

Download tmp file (from exports)

```php
public download(): mixed
```












***

### export_zip

Export applications in ZIP format

```php
public export_zip(mixed $fnums): string|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***


***
> Automatically generated on 2024-08-20
