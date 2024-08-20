***

# CategoryFeedView

Base feed View class for a category



* Full name: `\Joomla\CMS\MVC\View\CategoryFeedView`
* Parent class: [`\Joomla\CMS\MVC\View\AbstractView`](./AbstractView.md)




## Methods


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



***

### reconcileNames

Method to reconcile non standard names from components to usage in this class.

```php
protected reconcileNames(object $item): void
```

Typically overridden in the component feed view class.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$item` | **object** | The item for a feed, an element of the $items array. |





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
