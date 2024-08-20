***

# EmundusControllerRenew_application

Custom report controller



* Full name: `\EmundusControllerRenew_application`
* Parent class: [`JControllerLegacy`](./JControllerLegacy.md)




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

### export_zip

export ZIP

```php
public export_zip(): mixed
```












***

### cancel_renew

Cancel renew. Come back to previous application

```php
public cancel_renew(): mixed
```












***

### new_application

File new application. Define what to do

```php
public new_application(): mixed
```












***

### edit_user

Renew application. Define what to do/delete

```php
public edit_user(): mixed
```












***

### deleteReferents



```php
public deleteReferents(): mixed
```












***

### deleteApplication



```php
public deleteApplication(): mixed
```












***

### deleteInformations



```php
public deleteInformations(): mixed
```












***


***
> Automatically generated on 2024-08-20
