***

# WorkflowBehaviorTrait

Trait which supports state behavior



* Full name: `\Joomla\CMS\MVC\Model\WorkflowBehaviorTrait`



## Properties


### extension

The name of the component.

```php
protected string $extension
```






***

### section

The section of the component.

```php
protected string $section
```






***

### workflowEnabled

Is workflow for this component enabled?

```php
protected bool $workflowEnabled
```






***

### workflow

The workflow object

```php
protected \Joomla\CMS\Workflow\Workflow $workflow
```






***

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

### enableWorkflowBatch

Add the workflow batch to the command list. Can be overwritten by the child class

```php
protected enableWorkflowBatch(): void
```













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

### batchWorkflowStage

Batch change workflow stage or current.

```php
public batchWorkflowStage(int $value, array $pks, array $contexts): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **int** | The workflow stage ID. |
| `$pks` | **array** | An array of row IDs. |
| `$contexts` | **array** | An array of item contexts. |


**Return Value:**

An array of new IDs on success, boolean false on failure.





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

### importWorkflowPlugins

Import the Workflow plugins.

```php
protected importWorkflowPlugins(): void
```













***

### addTransitionField

Adds a transition field to the form. Can be overwritten by the child class if not needed

```php
protected addTransitionField(\Joomla\CMS\Form\Form $form, mixed $data): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form` | **\Joomla\CMS\Form\Form** | A Form object. |
| `$data` | **mixed** | The data expected for the form. |






***

### getStageForNewItem

Try to load a workflow stage for newly created items
which does not have a workflow assigned yet. If the category is not the
carrier, overwrite it on your model and deliver your own carrier.

```php
protected getStageForNewItem(\Joomla\CMS\Form\Form $form, mixed $data): bool|int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form` | **\Joomla\CMS\Form\Form** | A Form object. |
| `$data` | **mixed** | The data expected for the form. |


**Return Value:**

An integer, holding the stage ID or false





***

***
> Last updated on 20/08/2024

