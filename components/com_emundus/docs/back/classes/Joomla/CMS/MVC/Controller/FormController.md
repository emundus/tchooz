***

# FormController

Controller tailored to suit most form-based admin operations.



* Full name: `\Joomla\CMS\MVC\Controller\FormController`
* Parent class: [`\Joomla\CMS\MVC\Controller\BaseController`](./BaseController.md)
* This class implements:
[`\Joomla\CMS\Form\FormFactoryAwareInterface`](../../Form/FormFactoryAwareInterface.md)



## Properties


### app

The Application. Redeclared to show this class requires a web application.

```php
protected \Joomla\CMS\Application\CMSWebApplicationInterface $app
```






***

### context

The context for storing internal data, e.g. record.

```php
protected string $context
```






***

### option

The URL option for the component.

```php
protected string $option
```






***

### view_item

The URL view item variable.

```php
protected string $view_item
```






***

### view_list

The URL view list variable.

```php
protected string $view_list
```






***

### text_prefix

The prefix to use with controller messages.

```php
protected string $text_prefix
```






***

## Methods


### __construct

Constructor.

```php
public __construct(array $config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null, ?\Joomla\CMS\Application\CMSWebApplicationInterface $app = null, ?\Joomla\Input\Input $input = null, ?\Joomla\CMS\Form\FormFactoryInterface $formFactory = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An optional associative array of configuration settings.<br />Recognized key values include &#039;name&#039;, &#039;default_task&#039;,<br />&#039;model_path&#039;, and &#039;view_path&#039; (this list is not meant to be<br />comprehensive). |
| `$factory` | **?\Joomla\CMS\MVC\Factory\MVCFactoryInterface** | The factory. |
| `$app` | **?\Joomla\CMS\Application\CMSWebApplicationInterface** | The Application for the dispatcher |
| `$input` | **?\Joomla\Input\Input** | Input |
| `$formFactory` | **?\Joomla\CMS\Form\FormFactoryInterface** | The form factory. |






***

### add

Method to add a new record.

```php
public add(): bool
```









**Return Value:**

True if the record can be added, false if not.





***

### allowAdd

Method to check if you can add a new record.

```php
protected allowAdd(array $data = []): bool
```

Extended classes can override this if necessary.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | An array of input data. |






***

### allowEdit

Method to check if you can edit an existing record.

```php
protected allowEdit(array $data = [], string $key = &#039;id&#039;): bool
```

Extended classes can override this if necessary.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | An array of input data. |
| `$key` | **string** | The name of the key for the primary key; default is id. |






***

### allowSave

Method to check if you can save a new or existing record.

```php
protected allowSave(array $data, string $key = &#039;id&#039;): bool
```

Extended classes can override this if necessary.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | An array of input data. |
| `$key` | **string** | The name of the key for the primary key. |






***

### batch

Method to run batch operations.

```php
public batch(\Joomla\CMS\MVC\Model\BaseDatabaseModel $model): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model` | **\Joomla\CMS\MVC\Model\BaseDatabaseModel** | The model of the component being processed. |


**Return Value:**

True if successful, false otherwise and internal error is set.





***

### cancel

Method to cancel an edit.

```php
public cancel(string $key = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **string** | The name of the primary key of the URL variable. |


**Return Value:**

True if access level checks pass, false otherwise.





***

### edit

Method to edit an existing record.

```php
public edit(string $key = null, string $urlVar = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **string** | The name of the primary key of the URL variable. |
| `$urlVar` | **string** | The name of the URL variable if different from the primary key<br />(sometimes required to avoid router collisions). |


**Return Value:**

True if access level check and checkout passes, false otherwise.





***

### getModel

Method to get a model object, loading it if required.

```php
public getModel(string $name = &#039;&#039;, string $prefix = &#039;&#039;, array $config = [&#039;ignore_request&#039; =&gt; true]): \Joomla\CMS\MVC\Model\BaseDatabaseModel
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The model name. Optional. |
| `$prefix` | **string** | The class prefix. Optional. |
| `$config` | **array** | Configuration array for model. Optional. |


**Return Value:**

The model.





***

### getRedirectToItemAppend

Gets the URL arguments to append to an item redirect.

```php
protected getRedirectToItemAppend(int $recordId = null, string $urlVar = &#039;id&#039;): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$recordId` | **int** | The primary key id for the item. |
| `$urlVar` | **string** | The name of the URL variable for the id. |


**Return Value:**

The arguments to append to the redirect URL.





***

### getRedirectToListAppend

Gets the URL arguments to append to a list redirect.

```php
protected getRedirectToListAppend(): string
```









**Return Value:**

The arguments to append to the redirect URL.





***

### postSaveHook

Function that allows child controller access to model data
after the data has been saved.

```php
protected postSaveHook(\Joomla\CMS\MVC\Model\BaseDatabaseModel $model, array $validData = []): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model` | **\Joomla\CMS\MVC\Model\BaseDatabaseModel** | The data model object. |
| `$validData` | **array** | The validated data. |






***

### save

Method to save a record.

```php
public save(string $key = null, string $urlVar = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **string** | The name of the primary key of the URL variable. |
| `$urlVar` | **string** | The name of the URL variable if different from the primary key (sometimes required to avoid router collisions). |


**Return Value:**

True if successful, false otherwise.





***

### reload

Method to reload a record.

```php
public reload(string $key = null, string $urlVar = null): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **string** | The name of the primary key of the URL variable. |
| `$urlVar` | **string** | The name of the URL variable if different from the primary key (sometimes required to avoid router collisions). |






***

### editAssociations

Load item to edit associations in com_associations

```php
public editAssociations(): void
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.








***


## Inherited methods


### addModelPath

Adds to the stack of model paths in LIFO order.

```php
public static addModelPath(mixed $path, string $prefix = &#039;&#039;): void
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** | The directory (string), or list of directories (array) to add. |
| `$prefix` | **string** | A prefix for models |






***

### createFileName

Create the filename for a resource.

```php
public static createFileName(string $type, array $parts = []): string
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The resource type to create the filename for. |
| `$parts` | **array** | An associative array of filename information. Optional. |


**Return Value:**

The filename.





***

### getInstance

Method to get a singleton controller instance.

```php
public static getInstance(string $prefix, array $config = []): static
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prefix` | **string** | The prefix for the controller. |
| `$config` | **array** | An array of optional constructor options. |




**Throws:**
<p>if the controller cannot be loaded.</p>

- [`Exception`](../../../../Exception.md)




***

### __construct

Constructor.

```php
public __construct(array $config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null, ?\Joomla\CMS\Application\CMSApplicationInterface $app = null, ?\Joomla\Input\Input $input = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An optional associative array of configuration settings.<br />Recognized key values include &#039;name&#039;, &#039;default_task&#039;,<br />&#039;model_path&#039;, and &#039;view_path&#039; (this list is not meant to be<br />comprehensive). |
| `$factory` | **?\Joomla\CMS\MVC\Factory\MVCFactoryInterface** | The factory. |
| `$app` | **?\Joomla\CMS\Application\CMSApplicationInterface** | The Application for the dispatcher |
| `$input` | **?\Joomla\Input\Input** | Input |






***

### addPath

Adds to the search path for templates and resources.

```php
protected addPath(string $type, mixed $path): static
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The path type (e.g. &#039;model&#039;, &#039;view&#039;). |
| `$path` | **mixed** | The directory string  or stream array to search. |


**Return Value:**

A BaseController object to support chaining.





***

### addViewPath

Add one or more view paths to the controller's stack, in LIFO order.

```php
public addViewPath(mixed $path): static
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** | The directory (string) or list of directories (array) to add. |


**Return Value:**

This object to support chaining.





***

### checkEditId

Method to check whether an ID is in the edit list.

```php
protected checkEditId(string $context, int $id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$context` | **string** | The context for the session storage. |
| `$id` | **int** | The ID of the record to add to the edit list. |


**Return Value:**

True if the ID is in the edit list.





***

### createModel

Method to load and return a model object.

```php
protected createModel(string $name, string $prefix = &#039;&#039;, array $config = []): \Joomla\CMS\MVC\Model\BaseDatabaseModel|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the model. |
| `$prefix` | **string** | Optional model prefix. |
| `$config` | **array** | Configuration array for the model. Optional. |


**Return Value:**

Model object on success; otherwise false on failure.





***

### createView

Method to load and return a view object. This method first looks in the
current template directory for a match and, failing that, uses a default
set path to load the view class file.

```php
protected createView(string $name, string $prefix = &#039;&#039;, string $type = &#039;&#039;, array $config = []): \Joomla\CMS\MVC\View\ViewInterface|null
```

Note the "name, prefix, type" order of parameters, which differs from the
"name, type, prefix" order used in related public methods.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the view. |
| `$prefix` | **string** | Optional prefix for the view class name. |
| `$type` | **string** | The type of view. |
| `$config` | **array** | Configuration array for the view. Optional. |


**Return Value:**

View object on success; null or error result on failure.



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### display

Typical view method for MVC based architecture

```php
public display(bool $cachable = false, array $urlparams = []): static
```

This function is provide as a default implementation, in most cases
you will need to override it in your own controllers.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cachable` | **bool** | If true, the view output will be cached |
| `$urlparams` | **array** | An array of safe url parameters and their variable types.<br />@see        \Joomla\CMS\Filter\InputFilter::clean() for valid values. |


**Return Value:**

A \JControllerLegacy object to support chaining.



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### execute

Execute a task by triggering a method in the derived class.

```php
public execute(string $task): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$task` | **string** | The task to perform. If no matching task is found, the &#039;__default&#039; task is executed, if defined. |


**Return Value:**

The value returned by the called method.



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### getModel

Method to get a model object, loading it if required.

```php
public getModel(string $name = &#039;&#039;, string $prefix = &#039;&#039;, array $config = []): \Joomla\CMS\MVC\Model\BaseDatabaseModel|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The model name. Optional. |
| `$prefix` | **string** | The class prefix. Optional. |
| `$config` | **array** | Configuration array for model. Optional. |


**Return Value:**

Model object on success; otherwise false on failure.





***

### getName

Method to get the controller name

```php
public getName(): string
```

The dispatcher name is set by default parsed using the classname, or it can be set
by passing a $config['name'] in the class constructor







**Return Value:**

The name of the dispatcher



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### getTask

Get the last task that is being performed or was most recently performed.

```php
public getTask(): string
```









**Return Value:**

The task that is being performed or was most recently performed.





***

### getTasks

Gets the available tasks in the controller.

```php
public getTasks(): array
```









**Return Value:**

Array[i] of task names.





***

### getView

Method to get a reference to the current view and load it if necessary.

```php
public getView(string $name = &#039;&#039;, string $type = &#039;&#039;, string $prefix = &#039;&#039;, array $config = []): \Joomla\CMS\MVC\View\ViewInterface
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The view name. Optional, defaults to the controller name. |
| `$type` | **string** | The view type. Optional. |
| `$prefix` | **string** | The class prefix. Optional. |
| `$config` | **array** | Configuration array for view. Optional. |


**Return Value:**

Reference to the view or an error.



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### holdEditId

Method to add a record ID to the edit list.

```php
protected holdEditId(string $context, int $id): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$context` | **string** | The context for the session storage. |
| `$id` | **int** | The ID of the record to add to the edit list. |




**Throws:**

- [`Exception`](../../../../Exception.md)




***

### redirect

Redirects the browser or returns false if no redirect is set.

```php
public redirect(): bool
```









**Return Value:**

False if no redirect exists.



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### registerDefaultTask

Register the default task to perform if a mapping is not found.

```php
public registerDefaultTask(string $method): static
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$method` | **string** | The name of the method in the derived class to perform if a named task is not found. |


**Return Value:**

A \JControllerLegacy object to support chaining.





***

### registerTask

Register (map) a task to a method in the class.

```php
public registerTask(string $task, string $method): static
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$task` | **string** | The task. |
| `$method` | **string** | The name of the method in the derived class to perform for this task. |


**Return Value:**

A \JControllerLegacy object to support chaining.





***

### unregisterTask

Unregister (unmap) a task in the class.

```php
public unregisterTask(string $task): static
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$task` | **string** | The task. |


**Return Value:**

This object to support chaining.





***

### releaseEditId

Method to check whether an ID is in the edit list.

```php
protected releaseEditId(string $context, int $id): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$context` | **string** | The context for the session storage. |
| `$id` | **int** | The ID of the record to add to the edit list. |




**Throws:**

- [`Exception`](../../../../Exception.md)




***

### getLogger

Get the logger.

```php
protected getLogger(): \Psr\Log\LoggerInterface
```













***

### setMessage

Sets the internal message that is passed with a redirect

```php
public setMessage(string $text, string $type = &#039;message&#039;): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$text` | **string** | Message to display on redirect. |
| `$type` | **string** | Message type. Optional, defaults to &#039;message&#039;. |


**Return Value:**

Previous message





***

### setPath

Sets an entire array of search paths for resources.

```php
protected setPath(string $type, string $path): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The type of path to set, typically &#039;view&#039; or &#039;model&#039;. |
| `$path` | **string** | The new set of search paths. If null or false, resets to the current directory only. |






***

### checkToken

Checks for a form token in the request.

```php
public checkToken(string $method = &#039;post&#039;, bool $redirect = true): bool
```

Use in conjunction with HTMLHelper::_('form.token') or Session::getFormToken.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$method` | **string** | The request method in which to look for the token key. |
| `$redirect` | **bool** | Whether to implicitly redirect user to the referrer page on failure or simply return false. |


**Return Value:**

True if found and valid, otherwise return false or redirect to referrer page.



**Throws:**

- [`Exception`](../../../../Exception.md)




**See Also:**

* \Joomla\CMS\Session\Session::checkToken() - 

***

### setRedirect

Set a URL for browser redirection.

```php
public setRedirect(string $url, string $msg = null, string $type = null): static
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$url` | **string** | URL to redirect to. |
| `$msg` | **string** | Message to display on redirect. Optional, defaults to value set internally by controller, if any. |
| `$type` | **string** | Message type. Optional, defaults to &#039;message&#039; or the type set by a previous call to setMessage. |


**Return Value:**

This object to support chaining.





***

### prepareViewModel

Method to set the View Models

```php
protected prepareViewModel(\Joomla\CMS\MVC\View\ViewInterface $view): void
```

This function is provided as a default implementation,
and only set one Model in the view (that with the same prefix/sufix than the view).
In case you want to set several Models for your view,
you will need to override it in your DisplayController controller.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$view` | **\Joomla\CMS\MVC\View\ViewInterface** | The view Object |






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


***
> Last updated on 20/08/2024
