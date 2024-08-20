***

# EmundusControllerApplication

Emundus Application Controller



* Full name: `\EmundusControllerApplication`
* Parent class: [`\Joomla\CMS\MVC\Controller\BaseController`](./Joomla/CMS/MVC/Controller/BaseController.md)



## Properties


### _user

User object.

```php
private \Joomla\CMS\User\User|\JUser|mixed|null $_user
```






***

## Methods


### __construct

Constructor.

```php
public __construct(array $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An optional associative array of configuration settings. |





**See Also:**

* \JController - 

***

### display

Method to display a view.

```php
public display(bool $cachable = false, bool $urlparams = false): \EmundusControllerApplication
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cachable` | **bool** | If true, the view output will be cached. |
| `$urlparams` | **bool** | An array of safe URL parameters and their variable types.<br />@see        \Joomla\CMS\Filter\InputFilter::clean() for valid values. |


**Return Value:**

This object to support chaining.




***

### delete_attachments

Delete applicant attachments

```php
public delete_attachments(): mixed
```












***

### delete_attachment

Delete an applicant attachment by id (one by one)

```php
public delete_attachment(): mixed
```












***

### upload_attachment

Upload an applicant attachment (one by one)

```php
public upload_attachment(): mixed
```












***

### editcomment

Edit a comment

```php
public editcomment(): mixed
```












***

### deletecomment

Delete a comment

```php
public deletecomment(): mixed
```












***

### deletetag

Delete an application tag

```php
public deletetag(): mixed
```












***

### deletetraining

Delete training

```php
public deletetraining(): mixed
```












***

### getapplicationmenu

Get menus availables for an application file

```php
public getapplicationmenu(): mixed
```












***

### deleteattachement

Delete attachments by their ids and for a specific fnum

```php
public deleteattachement(): mixed
```












***

### exportpdf

Export an application file to PDF

```php
public exportpdf(): mixed
```












***

### updateaccess

Update access for an application file

```php
public updateaccess(): mixed
```












***

### deleteaccess

Remove access for an application file

```php
public deleteaccess(): mixed
```












***

### attachment_validation

Update validation state of an attachment for an application file

```php
public attachment_validation(): mixed
```












***

### getuserattachments

Get attachments for a specific user

```php
public getuserattachments(): mixed
```












***

### getattachmentsbyfnum

Get attachments for a specific fnum

```php
public getattachmentsbyfnum(): mixed
```












***

### updateattachment

Update file of an attachment for a specific fnum

```php
public updateattachment(): mixed
```












***

### getform

Get datas of an application for a specific fnum, can be filtered by form (profile_id)

```php
public getform(): mixed
```












***

### getattachmentpreview

Load a preview of an attachment

```php
public getattachmentpreview(): mixed
```












***

### reorderapplications

Reorder applications of a user

```php
public reorderapplications(): mixed
```












***

### createtab

Create a tab to group applications for the logged user

```php
public createtab(): mixed
```












***

### gettabs

Get tabs of the logged user

```php
public gettabs(): mixed
```












***

### updatetabs

Update tabs of the logged user

```php
public updatetabs(): mixed
```












***

### deletetab

Delete a tab of the logged user

```php
public deletetab(): mixed
```












***

### copyfile

Copy a file of the logged user to another campaign

```php
public copyfile(): mixed
```












***

### movetotab

Move an application file of the logged user to another tab

```php
public movetotab(): mixed
```












***

### renamefile

Rename an application file of the logged user

```php
public renamefile(): mixed
```












***

### getcampaignsavailableforcopy

Get campaigns available for copy for an application file of the logged user

```php
public getcampaignsavailableforcopy(): mixed
```












***

### filterapplications

Filter applications by order by and filter by

```php
public filterapplications(): mixed
```












***

### applicantcustomaction

Execute a custom action on an application file of the logged user

```php
public applicantcustomaction(): mixed
```












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

- [`Exception`](./Exception.md)



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

- [`Exception`](./Exception.md)



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

- [`Exception`](./Exception.md)



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

- [`Exception`](./Exception.md)



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

- [`Exception`](./Exception.md)



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

- [`Exception`](./Exception.md)



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

- [`Exception`](./Exception.md)



***

### redirect

Redirects the browser or returns false if no redirect is set.

```php
public redirect(): bool
```









**Return Value:**

False if no redirect exists.



**Throws:**

- [`Exception`](./Exception.md)



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

- [`Exception`](./Exception.md)



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

- [`Exception`](./Exception.md)



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

- [`UnexpectedValueException`](./UnexpectedValueException.md)



***


***
> Last updated on 20/08/2024
