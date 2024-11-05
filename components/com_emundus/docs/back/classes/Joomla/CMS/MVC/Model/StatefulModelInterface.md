***

# StatefulModelInterface

Interface for a stateful model.



* Full name: `\Joomla\CMS\MVC\Model\StatefulModelInterface`



## Methods


### getState

Method to get model state variables.

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

Method to set model state variables.

```php
public setState(string $property, mixed $value = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$property` | **string** | The name of the property. |
| `$value` | **mixed** | The value of the property to set or null. |


**Return Value:**

The previous value of the property or null if not set.





***


***
> Last updated on 20/08/2024
