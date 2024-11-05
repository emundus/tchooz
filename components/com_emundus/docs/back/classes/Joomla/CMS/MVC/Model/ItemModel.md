***

# ItemModel

Prototype item model.



* Full name: `\Joomla\CMS\MVC\Model\ItemModel`
* Parent class: [`\Joomla\CMS\MVC\Model\BaseDatabaseModel`](./BaseDatabaseModel.md)
* This class implements:
[`\Joomla\CMS\MVC\Model\ItemModelInterface`](./ItemModelInterface.md)
* This class is an **Abstract class**



## Properties


### _item

An item.

```php
protected array $_item
```






***

### _context

Model context string.

```php
protected string $_context
```






***

## Methods


### getStoreId

Method to get a store id based on model configuration state.

```php
protected getStoreId(string $id = &#039;&#039;): string
```

This is necessary because the model is used by the component and
different modules that might need different sets of data or different
ordering requirements.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **string** | A prefix for the store id. |


**Return Value:**

A store id.





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

### __construct

Constructor

```php
public __construct(array $config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An array of configuration options (name, state, dbo, table_path, ignore_request). |
| `$factory` | **?\Joomla\CMS\MVC\Factory\MVCFactoryInterface** | The factory. |




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

### getMVCFactory

Returns the MVC factory.

```php
protected getMVCFactory(): \Joomla\CMS\MVC\Factory\MVCFactoryInterface
```











**Throws:**

- [`UnexpectedValueException`](../../../../UnexpectedValueException.md)




***

### setMVCFactory

Set the MVC factory.

```php
public setMVCFactory(\Joomla\CMS\MVC\Factory\MVCFactoryInterface $mvcFactory): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mvcFactory` | **\Joomla\CMS\MVC\Factory\MVCFactoryInterface** | The MVC factory |






***

### _getList

Gets an array of objects from the results of database query.

```php
protected _getList(\Joomla\Database\DatabaseQuery|string $query, int $limitstart, int $limit): object[]
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query` | **\Joomla\Database\DatabaseQuery&#124;string** | The query. |
| `$limitstart` | **int** | Offset. |
| `$limit` | **int** | The number of records. |


**Return Value:**

An array of results.



**Throws:**

- [`RuntimeException`](../../../../RuntimeException.md)




***

### _getListCount

Returns a record count for the query.

```php
protected _getListCount(\Joomla\Database\DatabaseQuery|string $query): int
```

Note: Current implementation of this method assumes that getListQuery() returns a set of unique rows,
thus it uses SELECT COUNT(*) to count the rows. In cases that getListQuery() uses DISTINCT
then either this method must be overridden by a custom implementation at the derived Model Class
or a GROUP BY clause should be used to make the set unique.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query` | **\Joomla\Database\DatabaseQuery&#124;string** | The query. |


**Return Value:**

Number of rows for query.





***

### _createTable

Method to load and return a table object.

```php
protected _createTable(string $name, string $prefix = &#039;Table&#039;, array $config = []): \Joomla\CMS\Table\Table|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the view |
| `$prefix` | **string** | The class prefix. Optional. |
| `$config` | **array** | Configuration settings to pass to Table::getInstance |


**Return Value:**

Table object or boolean false if failed





**See Also:**

* \Joomla\CMS\Table\Table::getInstance() - 

***

### getTable

Method to get a table object, load it if necessary.

```php
public getTable(string $name = &#039;&#039;, string $prefix = &#039;&#039;, array $options = []): \Joomla\CMS\Table\Table
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The table name. Optional. |
| `$prefix` | **string** | The class prefix. Optional. |
| `$options` | **array** | Configuration array for model. Optional. |


**Return Value:**

A Table object



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### isCheckedOut

Method to check if the given record is checked out by the current user

```php
public isCheckedOut(\stdClass $item): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$item` | **\stdClass** | The record to check |






***

### cleanCache

Clean the cache

```php
protected cleanCache(string $group = null): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **string** | The cache group |






***

### bootComponent

Boots the component with the given name.

```php
protected bootComponent(string $component): \Joomla\CMS\Extension\ComponentInterface
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$component` | **string** | The component name, eg. com_content. |


**Return Value:**

The service container





***

### getDispatcher

Get the event dispatcher.

```php
public getDispatcher(): \Joomla\Event\DispatcherInterface
```

The override was made to keep a backward compatibility for legacy component.
TODO: Remove the override in 6.0









**Throws:**
<p>May be thrown if the dispatcher has not been set.</p>

- [`UnexpectedValueException`](../../../../UnexpectedValueException.md)




***

### dispatchEvent

Dispatches the given event on the internal dispatcher, does a fallback to the global one.

```php
protected dispatchEvent(\Joomla\Event\EventInterface $event): void
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$event` | **\Joomla\Event\EventInterface** | The event |






***

### getDbo

Get the database driver.

```php
public getDbo(): \Joomla\Database\DatabaseInterface
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.




**Return Value:**

The database driver.



**Throws:**

- [`UnexpectedValueException`](../../../../UnexpectedValueException.md)




***

### setDbo

Set the database driver.

```php
public setDbo(?\Joomla\Database\DatabaseInterface $db = null): void
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db` | **?\Joomla\Database\DatabaseInterface** | The database driver. |






***

### __get

Proxy for _db variable.

```php
public __get(string $name): mixed
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the element |


**Return Value:**

The value of the element if set, null otherwise





***


***
> Last updated on 20/08/2024
