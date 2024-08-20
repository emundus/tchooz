***

# ViewInterface

Joomla Platform CMS Interface



* Full name: `\Joomla\CMS\MVC\View\ViewInterface`



## Methods


### display

Execute and display a template script.

```php
public display(string $tpl = null): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tpl` | **string** | The name of the template file to parse; automatically searches through the template paths. |





***

### getModel

Method to get the model object

```php
public getModel(string $name = null): \Joomla\CMS\MVC\Model\BaseDatabaseModel
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the model (optional) |


**Return Value:**

The model object




***

### getName

Method to get the view name

```php
public getName(): string
```









**Return Value:**

The name of the view




***


***
> Last updated on 20/08/2024
