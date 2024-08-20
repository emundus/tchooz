***

# MVCFactoryInterface

Factory to create MVC objects.



* Full name: `\Joomla\CMS\MVC\Factory\MVCFactoryInterface`



## Methods


### createController

Method to load and return a controller object.

```php
public createController(string $name, string $prefix, array $config, \Joomla\CMS\Application\CMSApplicationInterface $app, \Joomla\Input\Input $input): \Joomla\CMS\MVC\Controller\ControllerInterface
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the controller |
| `$prefix` | **string** | The controller prefix |
| `$config` | **array** | The configuration array for the controller |
| `$app` | **\Joomla\CMS\Application\CMSApplicationInterface** | The app |
| `$input` | **\Joomla\Input\Input** | The input |




**Throws:**

- [`Exception`](../../../../Exception.md)




***

### createModel

Method to load and return a model object.

```php
public createModel(string $name, string $prefix = &#039;&#039;, array $config = []): \Joomla\CMS\MVC\Model\ModelInterface
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the model. |
| `$prefix` | **string** | Optional model prefix. |
| `$config` | **array** | Optional configuration array for the model. |


**Return Value:**

The model object



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### createView

Method to load and return a view object.

```php
public createView(string $name, string $prefix = &#039;&#039;, string $type = &#039;&#039;, array $config = []): \Joomla\CMS\MVC\View\ViewInterface
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the view. |
| `$prefix` | **string** | Optional view prefix. |
| `$type` | **string** | Optional type of view. |
| `$config` | **array** | Optional configuration array for the view. |


**Return Value:**

The view object



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### createTable

Method to load and return a table object.

```php
public createTable(string $name, string $prefix = &#039;&#039;, array $config = []): \Joomla\CMS\Table\Table
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the table. |
| `$prefix` | **string** | Optional table prefix. |
| `$config` | **array** | Optional configuration array for the table. |


**Return Value:**

The table object



**Throws:**

- [`Exception`](../../../../Exception.md)




***


***
> Last updated on 20/08/2024
