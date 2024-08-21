***

# StateBehaviorTrait

Trait which supports state behavior



* Full name: `\Joomla\CMS\MVC\Model\StateBehaviorTrait`



## Properties


### __state_set

Indicates if the internal state has been set

```php
protected bool $__state_set
```






***

### state

A state object

```php
protected \Joomla\CMS\MVC\Model\State $state
```






***

## Methods


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

### setState

Method to set state variables.

```php
public setState(string $property, mixed $value = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$property` | **string** | The name of the property |
| `$value` | **mixed** | The value of the property to set or null |


**Return Value:**

The previous value of the property or null if not set





***

### populateState

Method to auto-populate the state.

```php
protected populateState(): void
```

This method should only be called once per instantiation and is designed
to be called on the first call to the getState() method unless the
configuration flag to ignore the request is set.











***

***
> Last updated on 20/08/2024

