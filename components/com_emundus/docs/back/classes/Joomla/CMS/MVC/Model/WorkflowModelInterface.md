***

# WorkflowModelInterface

Interface for a workflow model.



* Full name: `\Joomla\CMS\MVC\Model\WorkflowModelInterface`



## Methods


### setUpWorkflow

Set Up the workflow

```php
public setUpWorkflow(string $extension): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$extension` | **string** | The option and section separated by. |






***

### workflowPreprocessForm

Method to allow derived classes to preprocess the form.

```php
public workflowPreprocessForm(\Joomla\CMS\Form\Form $form, mixed $data): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form` | **\Joomla\CMS\Form\Form** | A Form object. |
| `$data` | **mixed** | The data expected for the form. |




**Throws:**
<p>if there is an error in the form event.</p>

- [`Exception`](../../../../Exception.md)




**See Also:**

* \Joomla\CMS\MVC\Model\FormField - 

***

### workflowBeforeStageChange

Let plugins access stage change events

```php
public workflowBeforeStageChange(): void
```













***

### workflowBeforeSave

Preparation of workflow data/plugins

```php
public workflowBeforeSave(): void
```













***

### workflowAfterSave

Executing of relevant workflow methods

```php
public workflowAfterSave(mixed $data): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** |  |






***

### workflowCleanupBatchMove

Batch change workflow stage or current.

```php
public workflowCleanupBatchMove(int $oldId, int $newId): null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$oldId` | **int** | The ID of the item copied from |
| `$newId` | **int** | The ID of the new item |






***

### executeTransition

Runs transition for item.

```php
public executeTransition(array $pks, int $transitionId): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pks` | **array** | Id of items to execute the transition |
| `$transitionId` | **int** | Id of transition |






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


***
> Last updated on 20/08/2024
