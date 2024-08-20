***

# FormBehaviorTrait

Trait which supports form behavior.



* Full name: `\Joomla\CMS\MVC\Model\FormBehaviorTrait`



## Properties


### _forms

Array of form objects.

```php
protected \Joomla\CMS\Form\Form[] $_forms
```






***

## Methods


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

***
> Last updated on 20/08/2024

