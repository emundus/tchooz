***

# JFormFieldForeignKey

Supports a value from an external table



* Full name: `\JFormFieldForeignKey`
* Parent class: [`JFormField`](./JFormField.md)



## Properties


### type

The form field type.

```php
protected string $type
```






***

### input_type



```php
private $input_type
```






***

### table



```php
private $table
```






***

### key_field



```php
private $key_field
```






***

### value_field



```php
private $value_field
```






***

## Methods


### getInput

Method to get the field input markup.

```php
protected getInput(): string
```









**Return Value:**

The field input markup.




***

### getAttribute

Wrapper method for getting attributes from the form element

```php
public getAttribute(string $attr_name, mixed $default = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attr_name` | **string** | Attribute name |
| `$default` | **mixed** | Optional value to return if attribute not found |


**Return Value:**

The value of the attribute if it exists, null otherwise




***


***
> Automatically generated on 2024-08-02
