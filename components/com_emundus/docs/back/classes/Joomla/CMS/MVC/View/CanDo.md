***

# CanDo

A simple state holder class. This class acts for transition from CMSObject to Registry
and should not be used directly. Instead of, use the Registry class.



* Full name: `\Joomla\CMS\MVC\View\CanDo`
* Parent class: [`Registry`](../../../Registry/Registry.md)
* **Warning:** this class is **deprecated**. This means that this class will likely be removed in a future version.




## Methods


### __construct

Constructor

```php
public __construct(mixed $data = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **mixed** | The data to bind to the new Registry object. |






***

### get

Get a registry value.

```php
public get(string $path, mixed $default = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **string** | Registry path (e.g. joomla.content.showauthor) |
| `$default` | **mixed** | Optional default value, returned if the internal value is null. |


**Return Value:**

Value of entry or null





***

### getProperties

Returns an associative array of object properties.

```php
public getProperties(): array
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.




**Return Value:**

The data array





***

### __get

Proxy for internal data access for the given name.

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

### __set

Proxy for internal data storage for the given name and value.

```php
public __set(string $name, string $value): void
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the element |
| `$value` | **string** | The value |






***

### __isset

Proxy for internal data check for a variable with the given key.

```php
public __isset(string $name): bool
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the element |


**Return Value:**

Returns if the internal data storage contains a key with the given





***


***
> Last updated on 20/08/2024
