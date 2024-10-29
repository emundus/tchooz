***

# LegacyModelLoaderTrait

Trait which contains the legacy getInstance functionality



* Full name: `\Joomla\CMS\MVC\Model\LegacyModelLoaderTrait`
* **Warning:** this trait is **deprecated**. This means that this class will likely be removed in a future version.




## Methods


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

***
> Last updated on 20/08/2024

