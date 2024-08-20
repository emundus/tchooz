***

# EmundusControllerJob

Job controller class.



* Full name: `\EmundusControllerJob`
* Parent class: [`EmundusController`](./EmundusController.md)




## Methods


### apply

Method to apply to a job.

```php
public apply(): void
```












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

### cancel

Method to cancel application on a Job.

```php
public cancel(): void
```












***

### edit

Method to check out an item for editing and redirect to the edit form.

```php
public edit(): mixed
```












***

### publish

Method to save a user's profile data.

```php
public publish(): void
```












***

### remove



```php
public remove(): mixed
```












***


***
> Automatically generated on 2024-08-20
