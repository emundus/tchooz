***

# ApiMVCFactory

Factory to create MVC objects based on a namespace. Note that in an API Application model and table objects will be
created from their administrator counterparts.



* Full name: `\Joomla\CMS\MVC\Factory\ApiMVCFactory`
* Parent class: [`\Joomla\CMS\MVC\Factory\MVCFactory`](./MVCFactory.md)
* This class is marked as **final** and can't be subclassed
* This class is a **Final class**




## Methods


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


## Inherited methods


### __construct

The namespace must be like:
Joomla\Component\Content

```php
public __construct(string $namespace, \Psr\Log\LoggerInterface|null $logger = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$namespace` | **string** | The namespace |
| `$logger` | **\Psr\Log\LoggerInterface&#124;null** | A logging instance to inject into the controller if required |





***

### createController

Method to load and return a controller object.

```php
public createController(string $name, string $prefix, array $config, \Joomla\CMS\Application\CMSApplicationInterface $app, \Joomla\Input\Input $input): \Joomla\CMS\MVC\Controller\ControllerInterface|null
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

### getClassName

Returns a standard classname, if the class doesn't exist null is returned.

```php
protected getClassName(string $suffix, string $prefix): string|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$suffix` | **string** | The suffix |
| `$prefix` | **string** | The prefix |


**Return Value:**

The class name




***

### setFormFactoryOnObject

Sets the internal form factory on the given object.

```php
private setFormFactoryOnObject(object $object): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$object` | **object** | The object |





***

### setDispatcherOnObject

Sets the internal event dispatcher on the given object.

```php
private setDispatcherOnObject(object $object): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$object` | **object** | The object |





***

### setRouterOnObject

Sets the internal router on the given object.

```php
private setRouterOnObject(object $object): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$object` | **object** | The object |





***

### setCacheControllerOnObject

Sets the internal cache controller on the given object.

```php
private setCacheControllerOnObject(object $object): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$object` | **object** | The object |





***

### setUserFactoryOnObject

Sets the internal user factory on the given object.

```php
private setUserFactoryOnObject(object $object): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$object` | **object** | The object |





***

### setMailerFactoryOnObject

Sets the internal mailer factory on the given object.

```php
private setMailerFactoryOnObject(object $object): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$object` | **object** | The object |





***


***
> Last updated on 20/08/2024
