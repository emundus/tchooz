***

# BaseModel

Base class for a Joomla Model



* Full name: `\Joomla\CMS\MVC\Model\BaseModel`
* This class implements:
[`\Joomla\CMS\MVC\Model\ModelInterface`](./ModelInterface.md), [`\Joomla\CMS\MVC\Model\StatefulModelInterface`](./StatefulModelInterface.md)
* This class is an **Abstract class**



## Properties


### name

The model (base) name

```php
protected string $name
```






***

### paths

The include paths

```php
protected static array $paths
```



* This property is **static**.


***

## Methods


### __construct

Constructor

```php
public __construct(array $config = []): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An array of configuration options (name, state, ignore_request). |




**Throws:**

- [`Exception`](../../../../Exception.md)



***

### addIncludePath

Add a directory where \JModelLegacy should search for models. You may
either pass a string or an array of directories.

```php
public static addIncludePath(mixed $path = &#039;&#039;, string $prefix = &#039;&#039;): array
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** | A path or array[sting] of paths to search. |
| `$prefix` | **string** | A prefix for models. |


**Return Value:**

An array with directory elements. If prefix is equal to '', all directories are returned.




**See Also:**

*  - LegacyModelLoaderTrait::getInstance(...)

***

### getName

Method to get the model name

```php
public getName(): string
```

The model name. By default parsed using the classname or it can be set
by passing a $config['name'] in the class constructor







**Return Value:**

The name of the model



**Throws:**

- [`Exception`](../../../../Exception.md)



***


## Inherited methods


### _createFileName

Create the filename for a resource

```php
protected static _createFileName(string $type, array $parts = []): string
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The resource type to create the filename for. |
| `$parts` | **array** | An associative array of filename information. |


**Return Value:**

The filename




***

### getInstance

Returns a Model object, always creating it

```php
public static getInstance(string $type, string $prefix = &#039;&#039;, array $config = []): self|bool
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The model type to instantiate |
| `$prefix` | **string** | Prefix for the model class name. Optional. |
| `$config` | **array** | Configuration array for model. Optional. |


**Return Value:**

A \JModelLegacy instance or false on failure




***

### addTablePath

Adds to the stack of model table paths in LIFO order.

```php
public static addTablePath(mixed $path): void
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** | The directory as a string or directories as an array to add. |





***

### createModelFromComponent

Returns a Model object by loading the component from the prefix.

```php
private static createModelFromComponent(string $type, string $prefix = &#039;&#039;, array $config = []): \Joomla\CMS\MVC\Model\ModelInterface|null
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The model type to instantiate |
| `$prefix` | **string** | Prefix for the model class name. Optional. |
| `$config` | **array** | Configuration array for model. Optional. |


**Return Value:**

A ModelInterface instance or null on failure




***

### getState

Method to get state variables.

```php
public getState(string $property = null, mixed $default = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$property` | **string** | Optional parameter name |
| `$default` | **mixed** | Optional default value |


**Return Value:**

The property where specified, the state object where omitted




***

### setState

Method to set state variables.

```php
public setState(string $property, mixed $value = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$property` | **string** | The name of the property |
| `$value` | **mixed** | The value of the property to set or null |


**Return Value:**

The previous value of the property or null if not set




***

### populateState

Method to auto-populate the state.

```php
protected populateState(): void
```

This method should only be called once per instantiation and is designed
to be called on the first call to the getState() method unless the
configuration flag to ignore the request is set.










***


***
> Last updated on 20/08/2024
