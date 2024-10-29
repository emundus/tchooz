***

# HtmlView

Base class for a Joomla Html View

Class holding methods for displaying presentation data.

* Full name: `\Joomla\CMS\MVC\View\HtmlView`
* Parent class: [`\Joomla\CMS\MVC\View\AbstractView`](./AbstractView.md)
* This class implements:
[`\Joomla\CMS\User\CurrentUserInterface`](../../User/CurrentUserInterface.md)



## Properties


### _basePath

The base path of the view

```php
protected string $_basePath
```






***

### _layout

Layout name

```php
protected string $_layout
```






***

### _layoutExt

Layout extension

```php
protected string $_layoutExt
```






***

### _layoutTemplate

Layout template

```php
protected string $_layoutTemplate
```






***

### _path

The set of search directories for resources (templates)

```php
protected array $_path
```






***

### _template

The name of the default template source file.

```php
protected string $_template
```






***

### _output

The output of the template script.

```php
protected string $_output
```






***

### _charset

Charset to use in escaping mechanisms; defaults to urf8 (UTF-8)

```php
protected string $_charset
```






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
| `$config` | **array** | A named configuration array for object construction.<br />name: the name (optional) of the view (defaults to the view class name suffix).<br />charset: the character set to use for display<br />escape: the name (optional) of the function to use for escaping strings<br />base_path: the parent path (optional) of the views directory (defaults to the component folder)<br />template_plath: the path (optional) of the layout directory (defaults to base_path + /views/ + view name<br />helper_path: the path (optional) of the helper files (defaults to base_path + /helpers/)<br />layout: the layout (optional) to use to display the view |






***

### display

Execute and display a template script.

```php
public display(string $tpl = null): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tpl` | **string** | The name of the template file to parse; automatically searches through the template paths. |




**Throws:**

- [`Exception`](../../../../Exception.md)




**See Also:**

* \JViewLegacy::loadTemplate() - 

***

### escape

Escapes a value for output in a view script.

```php
public escape(mixed $var): mixed
```

If escaping mechanism is htmlspecialchars, use
{@link $_charset} setting.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$var` | **mixed** | The output to escape. |


**Return Value:**

The escaped value.





***

### getLayout

Get the layout.

```php
public getLayout(): string
```









**Return Value:**

The layout name





***

### getLayoutTemplate

Get the layout template.

```php
public getLayoutTemplate(): string
```









**Return Value:**

The layout template name





***

### setLayout

Sets the layout name to use

```php
public setLayout(string $layout): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$layout` | **string** | The layout name or a string in format &lt;template&gt;:&lt;layout file&gt; |


**Return Value:**

Previous value.





***

### setLayoutExt

Allows a different extension for the layout files to be used

```php
public setLayoutExt(string $value): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **string** | The extension. |


**Return Value:**

Previous value





***

### addTemplatePath

Adds to the stack of view script paths in LIFO order.

```php
public addTemplatePath(mixed $path): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** | A directory path or an array of paths. |






***

### addHelperPath

Adds to the stack of helper script paths in LIFO order.

```php
public addHelperPath(mixed $path): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** | A directory path or an array of paths. |






***

### loadTemplate

Load a template file -- first look in the templates folder for an override

```php
public loadTemplate(string $tpl = null): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tpl` | **string** | The name of the template source file; automatically searches the template paths and compiles as needed. |


**Return Value:**

The output of the template script.



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### loadHelper

Load a helper file

```php
public loadHelper(string $hlp = null): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$hlp` | **string** | The name of the helper source file automatically searches the helper paths and compiles as needed. |






***

### _setPath

Sets an entire array of search paths for templates or resources.

```php
protected _setPath(string $type, mixed $path): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The type of path to set, typically &#039;template&#039;. |
| `$path` | **mixed** | The new search path, or an array of search paths.  If null or false, resets to the current directory only. |






***

### _addPath

Adds to the search path for templates and resources.

```php
protected _addPath(string $type, mixed $path): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The type of path to add. |
| `$path` | **mixed** | The directory or stream, or an array of either, to search. |






***

### _createFileName

Create the filename for a resource

```php
protected _createFileName(string $type, array $parts = []): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The resource type to create the filename for |
| `$parts` | **array** | An associative array of filename information |


**Return Value:**

The filename





***

### getForm

Returns the form object

```php
public getForm(): mixed
```









**Return Value:**

A \JForm object on success, false on failure





***

### setDocumentTitle

Sets the document title according to Global Configuration options

```php
public setDocumentTitle(string $title): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$title` | **string** | The page title |






***


## Inherited methods


### __construct

Constructor

```php
public __construct(array $config = []): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | A named configuration array for object construction.<br />name: the name (optional) of the view (defaults to the view class name suffix).<br />charset: the character set to use for display<br />escape: the name (optional) of the function to use for escaping strings<br />base_path: the parent path (optional) of the views directory (defaults to the component folder)<br />template_plath: the path (optional) of the layout directory (defaults to base_path + /views/ + view name<br />helper_path: the path (optional) of the helper files (defaults to base_path + /helpers/)<br />layout: the layout (optional) to use to display the view |






***

### display

Execute and display a template script.

```php
public display(string $tpl = null): void
```




* This method is **abstract**.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tpl` | **string** | The name of the template file to parse; automatically searches through the template paths. |






***

### get

Method to get data from a registered model or a property of the view

```php
public get(string $property, string $default = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$property` | **string** | The name of the method to call on the model or the property to get |
| `$default` | **string** | The name of the model to reference or the default value [optional] |


**Return Value:**

The return value of the method





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

### setModel

Method to add a model to the view.  We support a multiple model single
view system by which models are referenced by classname.  A caveat to the
classname referencing is that any classname prepended by \JModel will be
referenced by the name without \JModel, eg. \JModelCategory is just
Category.

```php
public setModel(\Joomla\CMS\MVC\Model\BaseDatabaseModel $model, bool $default = false): \Joomla\CMS\MVC\Model\BaseDatabaseModel
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model` | **\Joomla\CMS\MVC\Model\BaseDatabaseModel** | The model to add to the view. |
| `$default` | **bool** | Is this the default model? |


**Return Value:**

The added model.





***

### getName

Method to get the view name

```php
public getName(): string
```

The model name by default parsed using the classname, or it can be set
by passing a $config['name'] in the class constructor







**Return Value:**

The name of the model



**Throws:**

- [`Exception`](../../../../Exception.md)




***

### getDocument

Get the Document.

```php
protected getDocument(): \Joomla\CMS\Document\Document
```











**Throws:**
<p>May be thrown if the document has not been set.</p>

- [`UnexpectedValueException`](../../../../UnexpectedValueException.md)




***

### setDocument

Set the document to use.

```php
public setDocument(\Joomla\CMS\Document\Document $document): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$document` | **\Joomla\CMS\Document\Document** | The document to use |






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


***
> Last updated on 20/08/2024
