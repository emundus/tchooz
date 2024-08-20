***

# AdminModel

Prototype admin model.



* Full name: `\Joomla\CMS\MVC\Model\AdminModel`
* Parent class: [`\Joomla\CMS\MVC\Model\FormModel`](./FormModel.md)
* This class is an **Abstract class**



## Properties


### typeAlias

The type alias for this content type (for example, 'com_content.article').

```php
public string $typeAlias
```






***

### text_prefix

The prefix to use with controller messages.

```php
protected string $text_prefix
```






***

### event_after_delete

The event to trigger after deleting the data.

```php
protected string $event_after_delete
```






***

### event_after_save

The event to trigger after saving the data.

```php
protected string $event_after_save
```






***

### event_before_delete

The event to trigger before deleting the data.

```php
protected string $event_before_delete
```






***

### event_before_save

The event to trigger before saving the data.

```php
protected string $event_before_save
```






***

### event_before_change_state

The event to trigger before changing the published state of the data.

```php
protected string $event_before_change_state
```






***

### event_change_state

The event to trigger after changing the published state of the data.

```php
protected string $event_change_state
```






***

### event_before_batch

The event to trigger before batch.

```php
protected string $event_before_batch
```






***

### batch_copymove

Batch copy/move command. If set to false,
the batch copy/move command is not supported

```php
protected string $batch_copymove
```






***

### batch_commands

Allowed batch commands

```php
protected array $batch_commands
```






***

### associationsContext

The context used for the associations table

```php
protected string $associationsContext
```






***

### batchSet

A flag to indicate if member variables for batch actions (and saveorder) have been initialized

```php
protected ?bool $batchSet
```






***

### user

The user performing the actions (re-usable in batch methods & saveorder(), initialized via initBatch())

```php
protected object $user
```






***

### table

A \Joomla\CMS\Table\Table instance (of appropriate type) to manage the DB records (re-usable in batch methods & saveorder(), initialized via initBatch())

```php
protected \Joomla\CMS\Table\Table $table
```






***

### tableClassName

The class name of the \Joomla\CMS\Table\Table instance managing the DB records (re-usable in batch methods & saveorder(), initialized via initBatch())

```php
protected string $tableClassName
```






***

### contentType

UCM Type corresponding to the current model class (re-usable in batch action methods, initialized via initBatch())

```php
protected object $contentType
```






***

### type

DB data of UCM Type corresponding to the current model class (re-usable in batch action methods, initialized via initBatch())

```php
protected object $type
```






***

## Methods


### __construct

Constructor.

```php
public __construct(array $config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null, ?\Joomla\CMS\Form\FormFactoryInterface $formFactory = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An array of configuration options (name, state, dbo, table_path, ignore_request). |
| `$factory` | **?\Joomla\CMS\MVC\Factory\MVCFactoryInterface** | The factory. |
| `$formFactory` | **?\Joomla\CMS\Form\FormFactoryInterface** | The form factory. |




**Throws:**

- [`Exception`](../../../../Exception.md)



***

### batch

Method to perform batch operations on an item or a set of items.

```php
public batch(array $commands, array $pks, array $contexts): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$commands` | **array** | An array of commands to perform. |
| `$pks` | **array** | An array of item ids. |
| `$contexts` | **array** | An array of item contexts. |


**Return Value:**

Returns true on success, false on failure.




***

### batchAccess

Batch access level changes for a group of rows.

```php
protected batchAccess(int $value, array $pks, array $contexts): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **int** | The new value matching an Asset Group ID. |
| `$pks` | **array** | An array of row IDs. |
| `$contexts` | **array** | An array of item contexts. |


**Return Value:**

True if successful, false otherwise and internal error is set.




***

### batchCopy

Batch copy items to a new category or current.

```php
protected batchCopy(int $value, array $pks, array $contexts): array|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **int** | The new category. |
| `$pks` | **array** | An array of row IDs. |
| `$contexts` | **array** | An array of item contexts. |


**Return Value:**

An array of new IDs on success, boolean false on failure.




***

### cleanupPostBatchCopy

Function that can be overridden to do any data cleanup after batch copying data

```php
protected cleanupPostBatchCopy(\Joomla\CMS\Table\TableInterface $table, int $newId, int $oldId): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table` | **\Joomla\CMS\Table\TableInterface** | The table object containing the newly created item |
| `$newId` | **int** | The id of the new item |
| `$oldId` | **int** | The original item id |





***

### batchLanguage

Batch language changes for a group of rows.

```php
protected batchLanguage(string $value, array $pks, array $contexts): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **string** | The new value matching a language. |
| `$pks` | **array** | An array of row IDs. |
| `$contexts` | **array** | An array of item contexts. |


**Return Value:**

True if successful, false otherwise and internal error is set.




***

### batchMove

Batch move items to a new category

```php
protected batchMove(int $value, array $pks, array $contexts): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **int** | The new category ID. |
| `$pks` | **array** | An array of row IDs. |
| `$contexts` | **array** | An array of item contexts. |


**Return Value:**

True if successful, false otherwise and internal error is set.




***

### batchTag

Batch tag a list of item.

```php
protected batchTag(int $value, array $pks, array $contexts): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **int** | The value of the new tag. |
| `$pks` | **array** | An array of row IDs. |
| `$contexts` | **array** | An array of item contexts. |


**Return Value:**

True if successful, false otherwise and internal error is set.




***

### canDelete

Method to test whether a record can be deleted.

```php
protected canDelete(object $record): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$record` | **object** | A record object. |


**Return Value:**

True if allowed to delete the record. Defaults to the permission for the component.




***

### canEditState

Method to test whether a record can have its state changed.

```php
protected canEditState(object $record): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$record` | **object** | A record object. |


**Return Value:**

True if allowed to change the state of the record. Defaults to the permission for the component.




***

### checkin

Method override to check-in a record or an array of record

```php
public checkin(mixed $pks = []): int|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pks` | **mixed** | The ID of the primary key or an array of IDs |


**Return Value:**

Boolean false if there is an error, otherwise the count of records checked in.




***

### checkout

Method override to check-out a record.

```php
public checkout(int $pk = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pk` | **int** | The ID of the primary key. |


**Return Value:**

True if successful, false if an error occurs.




***

### delete

Method to delete one or more records.

```php
public delete(array& $pks): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pks` | **array** | An array of record primary keys. |


**Return Value:**

True if successful, false if an error occurs.




***

### generateNewTitle

Method to change the title & alias.

```php
protected generateNewTitle(int $categoryId, string $alias, string $title): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$categoryId` | **int** | The id of the category. |
| `$alias` | **string** | The alias. |
| `$title` | **string** | The title. |


**Return Value:**

Contains the modified title and alias.




***

### getItem

Method to get a single record.

```php
public getItem(int $pk = null): \stdClass|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pk` | **int** | The id of the primary key. |


**Return Value:**

Object on success, false on failure.




***

### getReorderConditions

A protected method to get a set of ordering conditions.

```php
protected getReorderConditions(\Joomla\CMS\Table\Table $table): string[]
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table` | **\Joomla\CMS\Table\Table** | A Table object. |


**Return Value:**

An array of conditions to add to ordering queries.




***

### populateState

Stock method to auto-populate the model state.

```php
protected populateState(): void
```












***

### prepareTable

Prepare and sanitise the table data prior to saving.

```php
protected prepareTable(\Joomla\CMS\Table\Table $table): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table` | **\Joomla\CMS\Table\Table** | A reference to a Table object. |





***

### publish

Method to change the published state of one or more records.

```php
public publish(array& $pks, int $value = 1): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pks` | **array** | A list of the primary keys to change. |
| `$value` | **int** | The value of the published state. |


**Return Value:**

True on success.




***

### reorder

Method to adjust the ordering of a row.

```php
public reorder(int $pks, int $delta): bool|null
```

Returns NULL if the user did not have edit
privileges for any of the selected primary keys.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pks` | **int** | The ID of the primary key to move. |
| `$delta` | **int** | Increment, usually +1 or -1 |


**Return Value:**

False on failure or error, true on success, null if the $pk is empty (no items selected).




***

### save

Method to save the form data.

```php
public save(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | The form data. |


**Return Value:**

True on success, False on error.




***

### saveorder

Saves the manually set order of records.

```php
public saveorder(array $pks = [], int $order = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pks` | **array** | An array of primary key ids. |
| `$order` | **int** | +1 or -1 |


**Return Value:**

Boolean true on success, false on failure




***

### checkCategoryId

Method to check the validity of the category ID for batch copy and move

```php
protected checkCategoryId(int $categoryId): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$categoryId` | **int** | The category ID to check |





***

### generateTitle

A method to preprocess generating a new title in order to allow tables with alternative names
for alias and title to use the batch move and copy methods

```php
public generateTitle(int $categoryId, \Joomla\CMS\Table\Table $table): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$categoryId` | **int** | The target category id |
| `$table` | **\Joomla\CMS\Table\Table** | The Table within which move or copy is taking place |





***

### initBatch

Method to initialize member variables used by batch methods and other methods like saveorder()

```php
public initBatch(): void
```












***

### editAssociations

Method to load an item in com_associations.

```php
public editAssociations(array $data): bool
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | The form data. |


**Return Value:**

True if successful, false otherwise.




***

### redirectToAssociations

Method to load an item in com_associations.

```php
protected redirectToAssociations(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | The form data. |


**Return Value:**

True if successful, false otherwise.



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

### __construct

Constructor

```php
public __construct(array $config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null, ?\Joomla\CMS\Form\FormFactoryInterface $formFactory = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An array of configuration options (name, state, dbo, table_path, ignore_request). |
| `$factory` | **?\Joomla\CMS\MVC\Factory\MVCFactoryInterface** | The factory. |
| `$formFactory` | **?\Joomla\CMS\Form\FormFactoryInterface** | The form factory. |




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

### loadForm

Method to get a form object.

```php
protected loadForm(string $name, string $source = null, array $options = [], bool $clear = false, string $xpath = null): \Joomla\CMS\Form\Form
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the form. |
| `$source` | **string** | The form source. Can be XML string if file flag is set to false. |
| `$options` | **array** | Optional array of options for the form creation. |
| `$clear` | **bool** | Optional argument to force load a new form. |
| `$xpath` | **string** | An optional xpath to search for the fields. |




**Throws:**

- [`Exception`](../../../../Exception.md)



**See Also:**

* \Joomla\CMS\Form\Form - 

***

### loadFormData

Method to get the data that should be injected in the form.

```php
protected loadFormData(): array
```









**Return Value:**

The default data is an empty array.




***

### preprocessData

Method to allow derived classes to preprocess the data.

```php
protected preprocessData(string $context, mixed& $data, string $group = &#039;content&#039;): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$context` | **string** | The context identifier. |
| `$data` | **mixed** | The data to be processed. It gets altered directly. |
| `$group` | **string** | The name of the plugin group to import (defaults to &quot;content&quot;). |





***

### preprocessForm

Method to allow derived classes to preprocess the form.

```php
protected preprocessForm(\Joomla\CMS\Form\Form $form, mixed $data, string $group = &#039;content&#039;): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form` | **\Joomla\CMS\Form\Form** | A Form object. |
| `$data` | **mixed** | The data expected for the form. |
| `$group` | **string** | The name of the plugin group to import (defaults to &quot;content&quot;). |




**Throws:**
<p>if there is an error in the form event.</p>

- [`Exception`](../../../../Exception.md)



**See Also:**

* \Joomla\CMS\Form\FormField - 

***

### getFormFactory

Get the FormFactoryInterface.

```php
public getFormFactory(): \Joomla\CMS\Form\FormFactoryInterface
```




* This method is **abstract**.






**Throws:**
<p>May be thrown if the FormFactory has not been set.</p>

- [`UnexpectedValueException`](../../../../UnexpectedValueException.md)



***

### checkin

Method to checkin a row.

```php
public checkin(int $pk = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pk` | **int** | The numeric id of the primary key. |


**Return Value:**

False on failure or error, true otherwise.




***

### checkout

Method to check-out a row for editing.

```php
public checkout(int $pk = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pk` | **int** | The numeric id of the primary key. |


**Return Value:**

False on failure or error, true otherwise.




***

### validate

Method to validate the form data.

```php
public validate(\Joomla\CMS\Form\Form $form, array $data, string $group = null): array|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form` | **\Joomla\CMS\Form\Form** | The form to validate against. |
| `$data` | **array** | The data to validate. |
| `$group` | **string** | The name of the field group to validate. |


**Return Value:**

Array of filtered data if valid, false otherwise.




**See Also:**

* \Joomla\CMS\Form\FormRule - * \Joomla\CMS\Filter\InputFilter - 

***


***
> Last updated on 20/08/2024
