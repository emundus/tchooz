***

# EmundusModelProgramme

Model class for handling lists of items.



* Full name: `\EmundusModelProgramme`
* Parent class: [`\Joomla\CMS\MVC\Model\ListModel`](./Joomla/CMS/MVC/Model/ListModel.md)



## Properties


### app



```php
private $app
```






***

### _em_user



```php
private $_em_user
```






***

### _user



```php
private $_user
```






***

### _db



```php
protected $_db
```






***

### config



```php
private $config
```






***

## Methods


### __construct

Constructor

```php
public __construct(): mixed
```













***

### getCampaign

Method to get article data.

```php
public getCampaign(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |


**Return Value:**

Menu item data object on success, false on failure.





***

### getParams



```php
public getParams(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getAssociatedProgrammes



```php
public getAssociatedProgrammes(mixed $user): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |


**Return Value:**


get list of programmes for associated files





***

### getProgrammes



```php
public getProgrammes(mixed $published = null, mixed $codeList = array()): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$published` | **mixed** | int     get published or unpublished programme |
| `$codeList` | **mixed** | array   array of IN and NOT IN programme code to get |






***

### getProgramme



```php
public getProgramme(mixed $code): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |


**Return Value:**


get list of declared programmes





***

### addProgrammes



```php
public addProgrammes(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to add in table. |


**Return Value:**


Add new programme in DB





***

### editProgrammes



```php
public editProgrammes(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to add in table. |


**Return Value:**


Edit programme in DB





***

### getLatestProgramme

Gets the most recent programme code.

```php
public getLatestProgramme(): string
```









**Return Value:**

The most recently added programme in the DB.





***

### isFavorite

Checks if the user has this programme in his favorites.

```php
public isFavorite(mixed $programme_id, null $user_id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programme_id` | **mixed** | Int The ID of the programme to be favorited. |
| `$user_id` | **null** | Int The user ID, if null: the current user ID. |


**Return Value:**

True if favorited.





***

### favorite

Adds a programme to the user's list of favorites.

```php
public favorite(mixed $programme_id, null $user_id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programme_id` | **mixed** | Int The ID of the programme to be favorited. |
| `$user_id` | **null** | Int The user ID, if null: the current user ID. |






***

### unfavorite

Removes a programme from the user's list of favorites.

```php
public unfavorite(mixed $programme_id, null $user_id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programme_id` | **mixed** | Int The ID of the programme to be unfavorited. |
| `$user_id` | **null** | Int The user ID, if null: the current user ID. |






***

### getUpcomingFavorites

Get's the upcoming sessions of the user's favorite programs.

```php
public getUpcomingFavorites(null $user_id = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **null** |  |






***

### getFavorites

Get's the user's favorite programs.

```php
public getFavorites(null $user_id = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **null** |  |






***

### getAllPrograms



```php
public getAllPrograms(mixed $lim = &#039;all&#039;, mixed $page, mixed $filter = null, mixed $sort = &#039;DESC&#039;, mixed $recherche = null, mixed $user = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lim` | **mixed** |  |
| `$page` | **mixed** |  |
| `$filter` | **mixed** |  |
| `$sort` | **mixed** |  |
| `$recherche` | **mixed** |  |
| `$user` | **mixed** |  |






***

### getProgramCount



```php
public getProgramCount(mixed $filter, mixed $recherche): int|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filter` | **mixed** |  |
| `$recherche` | **mixed** |  |






***

### getProgramById



```php
public getProgramById(mixed $id): false|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### addProgram



```php
public addProgram(mixed $data, mixed $user = null): false|mixed|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |
| `$user` | **mixed** |  |






***

### updateProgram



```php
public updateProgram(int $id, array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **int** | the program to update |
| `$data` | **array** | the row to add in table. |


**Return Value:**


Update program in DB





***

### deleteProgram



```php
public deleteProgram(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to delete in table. |


**Return Value:**


Delete program(s) in DB





***

### unpublishProgram



```php
public unpublishProgram(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to unpublish in table. |


**Return Value:**


Unpublish program(s) in DB





***

### publishProgram



```php
public publishProgram(array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | the row to publish in table. |


**Return Value:**


Publish program(s) in DB





***

### getProgramCategories



```php
public getProgramCategories(): array
```









**Return Value:**


get list of declared programmes





***

### getYearsByProgram

get list of all campaigns associated to the user

```php
public getYearsByProgram(mixed $code): object
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |






***

### getManagers



```php
public getManagers(mixed $group): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |






***

### getEvaluators



```php
public getEvaluators(mixed $group): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |






***

### affectusertogroups



```php
public affectusertogroups(mixed $group, mixed $email, mixed $prog_group): false|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$email` | **mixed** |  |
| `$prog_group` | **mixed** |  |






***

### affectuserstogroup



```php
public affectuserstogroup(mixed $group, mixed $users, mixed $prog_group): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$users` | **mixed** |  |
| `$prog_group` | **mixed** |  |






***

### removefromgroup



```php
public removefromgroup(mixed $userid, mixed $group, mixed $prog_group): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$userid` | **mixed** |  |
| `$group` | **mixed** |  |
| `$prog_group` | **mixed** |  |






***

### getusers



```php
public getusers(mixed $filters, mixed $page = null): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filters` | **mixed** |  |
| `$page` | **mixed** |  |






***

### updateVisibility



```php
public updateVisibility(mixed $cid, mixed $gid, mixed $visibility): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |
| `$gid` | **mixed** |  |
| `$visibility` | **mixed** |  |






***

### clonegroup



```php
public clonegroup(mixed $gid): mixed|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |






***

### getEvaluationGrid



```php
public getEvaluationGrid(mixed $pid): false|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** |  |






***

### affectGroupToProgram



```php
public affectGroupToProgram(mixed $group, mixed $pid): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$pid` | **mixed** |  |






***

### deleteGroupFromProgram



```php
public deleteGroupFromProgram(mixed $group, mixed $pid): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$pid` | **mixed** |  |






***

### createGridFromModel



```php
public createGridFromModel(mixed $label, mixed $intro, mixed $model, mixed $pid): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$intro` | **mixed** |  |
| `$model` | **mixed** |  |
| `$pid` | **mixed** |  |






***

### getGridsModel



```php
public getGridsModel(): array|false|mixed
```













***

### createGrid



```php
public createGrid(mixed $label, mixed $intro, mixed $pid, mixed $template): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$intro` | **mixed** |  |
| `$pid` | **mixed** |  |
| `$template` | **mixed** |  |






***

### deleteGrid



```php
public deleteGrid(mixed $grid, mixed $pid): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$grid` | **mixed** |  |
| `$pid` | **mixed** |  |






***

### getUserPrograms



```php
public getUserPrograms(mixed $user_id): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |






***

### getGroupsByPrograms



```php
public getGroupsByPrograms(mixed $programs): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programs` | **mixed** |  |






***

### addGroupToProgram



```php
public addGroupToProgram(mixed $label, mixed $code, mixed $parent): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$code` | **mixed** |  |
| `$parent` | **mixed** |  |






***

### getGroupByParent



```php
public getGroupByParent(mixed $code, mixed $parent): false|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **mixed** |  |
| `$parent` | **mixed** |  |






***

### getCampaignsByProgram



```php
public getCampaignsByProgram(mixed $program): array|false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$program` | **mixed** |  |






***

### getAllSessions



```php
public getAllSessions(): mixed
```













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

Method to auto-populate the model state.

```php
protected populateState(string $ordering = null, string $direction = null): void
```

This method should only be called once per instantiation and is designed
to be called on the first call to the getState() method unless the model
configuration flag to ignore the request is set.

Note. Calling getState in this method will result in recursion.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ordering` | **string** | An optional ordering field. |
| `$direction` | **string** | An optional direction (asc&amp;#124;desc). |






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

- [`Exception`](./Exception.md)




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

- [`Exception`](./Exception.md)




***

### getMVCFactory

Returns the MVC factory.

```php
protected getMVCFactory(): \Joomla\CMS\MVC\Factory\MVCFactoryInterface
```











**Throws:**

- [`UnexpectedValueException`](./UnexpectedValueException.md)




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

- [`RuntimeException`](./RuntimeException.md)




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

- [`Exception`](./Exception.md)




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

- [`UnexpectedValueException`](./UnexpectedValueException.md)




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

- [`UnexpectedValueException`](./UnexpectedValueException.md)




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

- [`Exception`](./Exception.md)




**See Also:**

* \Joomla\CMS\Form\Form - 

***

### loadFormData

Method to get the data that should be injected in the form.

```php
protected loadFormData(): mixed
```









**Return Value:**

The data for the form.





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

- [`Exception`](./Exception.md)




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

- [`UnexpectedValueException`](./UnexpectedValueException.md)




***

### getEmptyStateQuery

Provide a query to be used to evaluate if this is an Empty State, can be overridden in the model to provide granular control.

```php
protected getEmptyStateQuery(): \Joomla\Database\DatabaseQuery
```













***

### getIsEmptyState

Is this an empty state, I.e: no items of this type regardless of the searched for states.

```php
public getIsEmptyState(): bool
```











**Throws:**

- [`Exception`](./Exception.md)




***

### _getListQuery

Method to cache the last query constructed.

```php
protected _getListQuery(): \Joomla\Database\DatabaseQuery
```

This method ensures that the query is constructed only once for a given state of the model.







**Return Value:**

A DatabaseQuery object





***

### getActiveFilters

Function to get the active filters

```php
public getActiveFilters(): array
```









**Return Value:**

Associative array in the format: array('filter_published' => 0)





***

### getItems

Method to get an array of data items.

```php
public getItems(): mixed
```









**Return Value:**

An array of data items on success, false on failure.





***

### getListQuery

Method to get a DatabaseQuery object for retrieving the data set from a database.

```php
protected getListQuery(): \Joomla\Database\DatabaseQuery|string
```









**Return Value:**

A DatabaseQuery object to retrieve the data set.





***

### getPagination

Method to get a \JPagination object for the data set.

```php
public getPagination(): \Joomla\CMS\Pagination\Pagination
```









**Return Value:**

A Pagination object for the data set.





***

### getStoreId

Method to get a store id based on the model configuration state.

```php
protected getStoreId(string $id = &#039;&#039;): string
```

This is necessary because the model is used by the component and
different modules that might need different sets of data or different
ordering requirements.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **string** | An identifier string to generate the store id. |


**Return Value:**

A store id.





***

### getTotal

Method to get the total number of items for the data set.

```php
public getTotal(): int
```









**Return Value:**

The total number of items available in the data set.





***

### getStart

Method to get the starting number of items for the data set.

```php
public getStart(): int
```









**Return Value:**

The starting number of items available in the data set.





***

### getFilterForm

Get the filter form

```php
public getFilterForm(array $data = [], bool $loadData = true): \Joomla\CMS\Form\Form|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | data |
| `$loadData` | **bool** | load current data |


**Return Value:**

The \JForm object or null if the form can't be found





***

### getUserStateFromRequest

Gets the value of a user state variable and sets it in the session

```php
public getUserStateFromRequest(string $key, string $request, string $default = null, string $type = &#039;none&#039;, bool $resetPage = true): mixed
```

This is the same as the method in Application except that this also can optionally
force you back to the first page when a filter has changed






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **string** | The key of the user state variable. |
| `$request` | **string** | The name of the variable passed in a request. |
| `$default` | **string** | The default value for the variable if not found. Optional. |
| `$type` | **string** | Filter for the variable. Optional.<br />@see        \Joomla\CMS\Filter\InputFilter::clean() for valid values. |
| `$resetPage` | **bool** | If true, the limitstart in request is set to zero |


**Return Value:**

The request user state.





***

### refineSearchStringToRegex

Parse and transform the search string into a string fit for regex-ing arbitrary strings against

```php
protected refineSearchStringToRegex(string $search, string $regexDelimiter = &#039;/&#039;): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$search` | **string** | The search string |
| `$regexDelimiter` | **string** | The regex delimiter to use for the quoting |


**Return Value:**

Search string escaped for regex





***


***
> Last updated on 20/08/2024
